--TEST--
unix socket udp server

--SKIPIF--
<?php require  __DIR__ . "/../inc/skipif.inc"; ?>
--INI--
assert.active=1
assert.warning=1
assert.bail=0
assert.quiet_eval=0

--FILE--
<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/6/7
 * Time: 下午3:50
 */

require_once __DIR__ . "/../inc/zan.inc";

$pid = pcntl_fork();
if ($pid < 0) {
    exit;
}

if ($pid === 0) {
    usleep(100);
    $client = new \swoole_client(SWOOLE_SOCK_UNIX_DGRAM, SWOOLE_SOCK_SYNC);
    $r = $client->connect(UNIXSOCK_SERVER_PATH, 0, -1);
    if ($r === false) {
        echo "ERROR";exit;
    }
    $client->send("SUCCESS");

    // TODO
    echo $client->recv();
    $client->close();
    exit;
} else {
    $serv = new \swoole_server(UNIXSOCK_SERVER_PATH, 0, SWOOLE_PROCESS, SWOOLE_UNIX_DGRAM);
    $serv->set([ "worker_num" => 1, ]);
    $serv->on("start", function(\swoole_server $serv) use($pid) {
        swoole_timer_after(1000, function() use($serv, $pid) {
            @unlink(UNIXSOCK_SERVER_PATH);
            pcntl_waitpid($pid, $status);
            $serv->shutdown();
        });
    });
    $serv->on("packet", function (\swoole_server $serv, $data, $addr) {
        var_dump($data);
        var_dump($addr);
        // TODO
        $serv->send($addr['address'], json_encode(array("hello" => $data, "addr" => $addr)).PHP_EOL);
    });
    $serv->start();
}

?>

--EXPECT--
SUCCESS


