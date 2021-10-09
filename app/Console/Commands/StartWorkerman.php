<?php

namespace App\Console\Commands;

use App\GatewayWorker\Events;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use Illuminate\Console\Command;
use Workerman\Worker;

class StartWorkerman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:workerman {action} {--daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动workerman';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        global $argv;

        if (!in_array($action = $this->argument('action'), ['start', 'stop', 'restart'])) {
            $this->error('Error Arguments');
            exit;
        }

        $argv[0] = 'gateway-worker:server';
        $argv[1] = $action;
        $argv[2] = $this->option('daemon') ? '-d' : '';

        $this->start();
    }

    private function start()
    {
        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }

    private function startBusinessWorker()
    {
        $worker                  = new BusinessWorker();
        $worker->name            = 'BusinessWorker';                        #设置BusinessWorker进程的名称
        $worker->count           = 1;                                       #设置BusinessWorker进程的数量
        $worker->registerAddress = '127.0.0.1:1236';                        #注册服务地址
        $worker->eventHandler    = Events::class;            #设置使用哪个类来处理业务,业务类至少要实现onMessage静态方法，onConnect和onClose静态方法可以不用实现
    }

    private function startGateWay()
    {
        $gateway = new Gateway("websocket://0.0.0.0:2346");
        $gateway->name                 = 'Gateway';                         #设置Gateway进程的名称，方便status命令中查看统计
        $gateway->count                = 1;                                 #进程的数量
        $gateway->lanIp                = '127.0.0.1';                       #内网ip,多服务器分布式部署的时候需要填写真实的内网ip
        $gateway->startPort            = 2300;                              #监听本机端口的起始端口
        $gateway->pingInterval         = 30;
        $gateway->pingNotResponseLimit = 0;                                 #服务端主动发送心跳
        $gateway->pingData             = '{"mode":"heart"}';
        $gateway->registerAddress      = '127.0.0.1:1236';                  #注册服务地址
    }

    private function startRegister()
    {
        new Register('text://0.0.0.0:1236');
    }
}
