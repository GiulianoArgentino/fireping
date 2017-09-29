<?php
namespace AppBundle\Command;

use AppBundle\Instruction\Instruction;
use AppBundle\Instruction\InstructionBuilder;
use AppBundle\Probe\EchoPoster;
use AppBundle\Probe\HttpPoster;
use AppBundle\Probe\Message;
use AppBundle\Probe\MessageQueueHandler;
use AppBundle\Probe\MessageQueue;
use AppBundle\Probe\ProbeDefinition;
use AppBundle\Probe\DeviceDefinition;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Class ProbeDispatcherCommand
 * @package AppBundle\Command
 */
class ProbeDispatcherCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $processes = array();

    /**
     * @var array
     */
    protected $inputs = array();

    /** @var \SplQueue */
    protected $queue;

    /** @var boolean */
    protected $queueLock;

    /** @var MessageQueueHandler */
    protected $queueHandler;

    protected $workerLimit;

    /** @var KernelInterface */
    protected $kernel;

    /** @var LoggerInterface */
    protected $logger;

    protected function configure()
    {
        $this
            ->setName('app:probe:dispatcher')
            ->setDescription('Start the probe dispatcher.')
            ->addOption(
                'workers-limit',
                'w',
                InputOption::VALUE_REQUIRED,
                'How many workers can be created at most?',
                50
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->kernel = $this->getContainer()->get('kernel');
        $this->logger = $this->getContainer()->get('logger');
        $id = $this->getContainer()->getParameter('slave.name');
        $poster = new EchoPoster("https://smokeping-dev.cegeka.be/api/slaves/$id/result");
        $this->queueHandler = new MessageQueueHandler($poster);
        $this->queueHandler->addQueue(new MessageQueue('data'));
        $this->queueHandler->addQueue(new MessageQueue('exceptions'));

        $this->workerLimit = $input->getOption('workers-limit');

        $this->queue = new \SplQueue();
        $pid = getmypid();
        $now = date('l jS \of F Y h:i:s A');

        $this->logger->info("Slave started at $now");

        $loop = Factory::create();

        $probeStore = $this->getContainer()->get('probe_store');

        $loop->addPeriodicTimer(60, function () use ($pid, $probeStore) {
            $this->logger->info("ProbeStore Sync Started");
            $probeStore->sync($this->logger);
        });

        $loop->addPeriodicTimer(1, function () use ($probeStore) {
            foreach ($probeStore->getProbes() as $probe) {
                /* @var $probe ProbeDefinition */
                $now = time();
                $remainder = $now % $probe->getStep();

                if (!$remainder) {

                    $instructionBuilder = $this->getContainer()->get('instruction_builder');
                    $instructions = $instructionBuilder->create($probe);

                    /* @var $instructions Instruction */
                    foreach ($instructions->getChunks() as $instruction) {
                        try {
                            $worker = $this->getWorker();
                        } catch (\Exception $exception) {
                            $this->queueHandler->addMessage('exceptions', new Message(
                                MESSAGE::SERVER_ERROR,
                                'Workers limit reached.',
                                array(
                                    get_class($exception) => $exception->getMessage(),
                                )
                            ));
                            break;
                        }
                        $workerPid = $worker->getPid();
                        $input = $this->getInput($workerPid);
                        $instruction = json_encode($instruction);
                        $this->logger->info("Sending instruction to pid/$workerPid: $instruction");
                        $input->write($instruction);
                    }
                }
            }
        });

        $loop->addPeriodicTimer(1 * 60, function () {
            $x = $this->queue->count();
            $this->logger->info("Queue currently has $x items left to be processed.");
            if (!$this->queueLock) {
                $this->queueLock = true;
                while (!$this->queue->isEmpty()) {
                    $node = $this->queue->shift();
                    try {
                        $this->postResults($node);
                    } catch (TransferException $exception) {
                        if ($exception->getCode() === 409) {
                            // Conflict detected, discard the message.
                            $this->logger->warning("Master indicates that we are attempting to update the past, discarding message.");
                        } elseif ($exception->getCode() === 500) {
                            // TODO: This should probably be more specific but the master currently returns 500 if we send incorrect data.
                            $this->logger->error("Master indicates an error, discarding data... \n" . $exception->getMessage());
                        } else {
                            $this->queue->unshift($node);
                            $this->queueLock = false;
                            break;
                        }
                    }
                }
                $this->queueLock = false;
            } else {
                $this->logger->warning("Queue is currently locked.");
            }
        });

        $loop->addPeriodicTimer(1 * 30, function () {
            $this->logger->info("Processing queues.");
            $this->queueHandler->processQueues();
        });

        $loop->addPeriodicTimer(0.1, function () {
            foreach ($this->processes as $pid => $process) {
                try {
                    if ($process) {
                        $process->checkTimeout();
                        $process->getIncrementalOutput();
                    }
                } catch (ProcessTimedOutException $exception) {
                    $this->cleanup($pid);
                }
            }
        });

        $this->logger->info("Initializing ProbeStore.");
        $probeStore->sync($this->logger);

        $loop->run();
    }

    private function handleResponse($type, $data)
    {
        $decoded = json_decode($data, true);

        if (!$decoded) {
            $this->logger->warning("$data could not be decoded to JSON.");
            return;
        }

        if (!isset($decoded['status'], $decoded['message'], $decoded['body']))
        {
            $this->logger->warning('One more or required keys {status,message,body} are missing from worker response: ' . json_encode($decoded));
            return;
        }

        if ($decoded['status'] == 500) {
            //TODO: This is temporary!!
            $this->logger->info($data);
        } else {
            // TODO: Handle different status codes.  Right now, we assume that only data is sent.
            // TODO: Should also handle client and server errors.
            $cleaned = array();
            foreach ($decoded['body'] as $id => $message) {
                if (!isset($message['type'], $message['timestamp'], $message['targets'])) {
                    $this->logger->warning('One or more required keys {type, timestamp, targets} are missing from the response body: ' . json_encode($decoded));
                    continue; // Do not attempt to post incomplete results.
                }
                $cleaned[$id] = $message;
            }
//        if ($decoded['status'] == Message::MESSAGE_OK) {
//            $this->logger->info("Adding message to data queue.");
//            $this->queueHandler->addMessage('data', new Message(
//                Message::MESSAGE_OK,
//                'Message OK',
//                $cleaned
//            ));
//        } else {
//            $this->logger->info("Adding message to exceptions queue.");
//            $this->queueHandler->addMessage('exceptions', new Message(
//                Message::SERVER_ERROR,
//                'An error...',
//                $decoded
//            ));
//        }
            $this->queue->enqueue($cleaned);
        }
    }

    /**
     * Posts the formatted results from any probe to the master.
     *
     * @param array $results
     */
    private function postResults(array $results)
    {
        $id = $this->getContainer()->getParameter('slave.name');
        $prod_endpoint = "https://smokeping-dev.cegeka.be/api/slaves/$id/result";
        $dev_endpoint = "http://localhost/api/slaves/$id/result";
        $endpoint = $prod_endpoint;

        $client = new Client();

        $data = json_encode($results);
        try {
            $this->logger->info("Posting $endpoint with data: $data");
            $response = $client->post($endpoint, [
                'body' => json_encode($results),
            ]);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            $this->logger->info("Response ($statusCode) from $endpoint: $body");
        } catch (TransferException $exception) {
            $message = $exception->getMessage();
            $this->logger->error("Exception (message: $message) was thrown while posting data to $endpoint.");
            throw $exception; // TODO: This probably just shouldn't throw any exceptions and instead let the timer handle it.
        }
    }

    /**
     * Get a new Worker process.
     *
     * @return Process
     */
    private function getWorker()
    {
        if (count($this->processes) > $this->workerLimit) {
            throw new \Exception("Worker limit reached.");
        }

        $executable = $this->kernel->getRootDir() . '/../bin/console';
        $environment = $this->kernel->getEnvironment();
        $process = new Process("exec php $executable app:probe:worker --env=$environment -vvv");
        $input = new InputStream();
        $process->setInput($input);
        $process->setTimeout(180);
        $process->setIdleTimeout(60);
        $process->start(function ($type, $data) use ($process) {
            $pid = $process->getPid();
            $this->handleResponse($type, $data);
            $this->logger->info("Killing Process/$pid");
            $this->cleanup($pid);
        });
        $pid = $process->getPid();
        $this->logger->info("Started Process/$pid");

        $this->processes[$pid] = $process;
        $this->inputs[$pid] = $input;

        return $process;
    }

    /**
     * Get or create a new InputStream for a given $id.
     *
     * @param $pid
     * @return mixed
     */
    private function getInput($pid) {
        if (!isset($this->processes[$pid])) {
            throw new \Exception("Process for PID=$pid not found.");
        }

        if (!isset($this->inputs[$pid])) {
            throw new \Exception("Input for PID=$pid not found.");
        }

        return $this->inputs[$pid];
    }

    /**
     * Dereferences old processes and inputs.
     *
     * @param $id
     */
    private function cleanup($id)
    {
        if (isset($this->processes[$id])) {
            $this->processes[$id]->stop(3, SIGINT);
            $this->processes[$id] = null;
            unset($this->processes[$id]);
        }

        if (isset($this->inputs[$id])) {
            $this->inputs[$id] = null;
            unset($this->inputs[$id]);
        }
    }
}