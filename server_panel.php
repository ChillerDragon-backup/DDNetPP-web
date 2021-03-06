<?php
require_once(__DIR__ . "/global.php");
require_once(__DIR__ . "/ClientInfos.php");
session_start();
if (IS_MINER == true)
{
    StartMiner();
}
HtmlHeader("ServerPanel");
?>
        <link rel="stylesheet" href="design/server_panel.css"></style>
        <div id="panel-nav">
            <ul id="panel-list">
                <li><a href="server_panel.php?p=home">Main</a></li>
                <li><a href="server_panel.php?p=test">Test</a></li>
                <li><a href="server_panel.php?p=logs">Logs</a></li>
            </ul>
        </div>
<?php

if (empty($_SESSION['csLOGGED']) || $_SESSION['csLOGGED'] !== "online")
{
	echo "you are not logged in";
    fok(); // should include die() but
    die(); // better double check on that one
}

function PageTest()
{
    echo "<h1>Test server</h1>";
    if (!empty($_GET['action']))
    {
        $action = isset($_GET['action'])? $_GET['action'] : '';
        $action = (string)$action;
        $out = "error: action not found";
        if ($action === "github_update")
        {
            $cmd = "cd " . SCRIPTS_TEST_SRV_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_TEST_SRV_PATH . "/github_update.sh";
            //echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        if ($action === "github_update_tmp")
        {
            if (IS_ALLOWED_TMP_GITHUB)
            {
                $cmd = "cd " . SCRIPTS_TEST_SRV_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_TEST_SRV_PATH . "/github_update_tmp.sh";
                //echo "cmd: <br/>$cmd<br/>";
                $out = shell_exec($cmd);
            }
            else
            {
                echo "tmp github update is currently not allowed";
            }
        }
        else if ($action === "start")
        {
            $cmd = "cd " . SCRIPTS_TEST_SRV_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_TEST_SRV_PATH . "/start.sh";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "stop")
        {
            $cmd = "cd " . SCRIPTS_TEST_SRV_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_TEST_SRV_PATH . "/stop.sh";
            //$cmd = "cd " . SCRIPTS_TEST_SRV_PATH . ";sudo " . SCRIPTS_TEST_SRV_PATH . "/stop.sh";
            //echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "log")
        {
            $cmd = "cat " . SCRIPTS_TEST_SRV_PATH . "/logs/test.log";
            //$cmd = "cd " . SCRIPTS_TEST_SRV_PATH . ";sudo " . SCRIPTS_TEST_SRV_PATH . "/stop.sh";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        $out = nl2br($out);
        echo "<br/>out:<br/> $out";
    }
    else
    {
        //echo "no action";
    }
?>
    <form method="get">
    <br/><input type="button" value="start" onclick="window.location.href='server_panel.php?p=test&action=start'"/>
    <br/><input type="button" value="stop" onclick="window.location.href='server_panel.php?p=test&action=stop'"/>
    <br/><input type="button" value="show log" onclick="window.location.href='server_panel.php?p=test&action=log'"/>
    <br/><input type="button" value="github_update" onclick="window.location.href='server_panel.php?p=test&action=github_update'"/>
    <br/><input type="button" value="github_update_tmp" onclick="window.location.href='server_panel.php?p=test&action=github_update_tmp'"/>
    </form>
<?php
}

function ShowLogs()
{
    $db = new PDO(WEB_DATABASE_PATH);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    $rows = $db->query('SELECT * FROM ServerPanel ORDER BY ID DESC LIMIT 10');

    if ($rows)
    {
        echo "<h2>Last 10 server panel actions</h2>";
        $rows = $rows->fetchAll();
        foreach($rows as $row)
        {
            $username = $row['Username'];
            $action = $row['Action'];
            $time = $row['TimeStamp'];
            if(strpos($action, "restart_chillerbot_") !== FALSE)
            {
                echo "[$time] <font color=\"red\"><b>$username</b>: $action<br/></font>";
            }
            else
            {
                echo "[$time] <b>$username</b>: $action<br/>";
            }
        }
    }
    else
    {
        echo "No logs yet.";
    }
}

function LogServerPanelAction($action)
{
        //Get Date
        $current_date = date_create(date("Y-m-d H:i:s"));
        $current_date_str = $current_date->format('Y-m-d H:i:s');
        
        //Get City
        $ip = $_SERVER['REMOTE_ADDR'];
        $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
        //echo "City: " . $details->city;
        
        //Get Operating system
        $user_os = getOS($_SERVER['HTTP_USER_AGENT']);
        
        //Get Browser
        $user_browser = getBrowser($_SERVER['HTTP_USER_AGENT']);
        
        //Add login to login history
        $db = NULL;
	    $db = new PDO(WEB_DATABASE_PATH);
	    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	    $stmt = $db->prepare('INSERT INTO ServerPanel (Username, Action, TimeStamp, IP, Location, Browser, OS, OtherDetails) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
	    $stmt->execute(array($_SESSION['Username'], $action, $current_date_str, $_SERVER['REMOTE_ADDR'], $details->city, $user_browser, $user_os, $_SERVER['HTTP_USER_AGENT']));
}

$db = new PDO(ABSOLUTE_DATABASE_PATH);
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
$stmt = $db->prepare('SELECT * FROM Accounts WHERE ID = ? ');
$stmt->execute(array($_SESSION['csID']));

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($rows)
{
	$username = $rows[0]['Username'];
	$IsSuperMod = $rows[0]['IsSuperModerator'];
	$IsSupporter = $rows[0]['IsSupporter'];
	if ($IsSupporter !== "1")
	{
		echo "missing permission.<br>";
        fok();
		die(); //safety 2dies
	}


    if (!empty($_GET['p']))
    {
        $page = isset($_GET['p'])? $_GET['p'] : '';
        $page = (string)$page;
        if ($page === "logs")
        {
            ShowLogs();
            fok();
            die();
        }
        else if ($page === "test")
        {
            PageTest();
            fok();
            die();
        }
    }

    if (!empty($_GET['action']))
    {
        $action = isset($_GET['action'])? $_GET['action'] : '';
        $action = (string)$action;
        $out = "error: action not found";
        if ($action === "start_restart")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/init_restart.sh";
            //echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "stop_restart")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/cancle_restart.sh";
            //echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "status_restart")
        {
            $out = shell_exec("cat " . SCRIPTS_PATH . "/status_restart_*.log");
        }
        else if ($action === "start_github")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/github_update.sh";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "restart_chillerbot_bl")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/restart_bot_bl.sh";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "restart_chillerbot_cb")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/restart_bot_cb.sh";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "update_ddpp_scripts")
        {
            $cmd = "sudo -u " . DDPP_USER . " " . UPDATE_SCRIPTS_SCRIPT_PATH;
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "ddpp_shutdown_cb_on")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/ddpp_shutdown_chillblock.sh 2 1";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "ddpp_shutdown_bl_on")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/ddpp_shutdown_BlmapChill.sh 2 1";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "ddpp_shutdown_cb_off")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/ddpp_shutdown_chillblock.sh 2 0";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        else if ($action === "ddpp_shutdown_bl_off")
        {
            $cmd = "cd " . SCRIPTS_PATH . ";sudo -u " . DDPP_USER . " " . SCRIPTS_PATH . "/ddpp_shutdown_BlmapChill.sh 2 0";
            echo "cmd: <br/>$cmd<br/>";
            $out = shell_exec($cmd);
        }
        $out = nl2br($out);
        echo "Output:<br/>$out";
        LogServerPanelAction($action);
    }
?>
    <form method="get">
    <h1 id="zone-night">Night restart (FORCE)</h1>
    <br/><input type="button" value="start restart.sh" onclick="window.location.href='server_panel.php?action=start_restart'"/>
    <br/><input type="button" value="stop restart.sh" onclick="window.location.href='server_panel.php?action=stop_restart'"/>
    <br/><input type="button" value="status restart.sh" onclick="window.location.href='server_panel.php?action=status_restart'"/>
    <h1 id="zone-github">Night(02:00) veto vote shutdown (restart)</h1>
    <br/><input type="button" value="start chillblock" onclick="window.location.href='server_panel.php?action=ddpp_shutdown_cb_on'"/>
    <br/><input type="button" value="stop chillblock" onclick="window.location.href='server_panel.php?action=ddpp_shutdown_cb_off'"/>
    <br/><input type="button" value="start blmapchill" onclick="window.location.href='server_panel.php?action=ddpp_shutdown_bl_on'"/>
    <br/><input type="button" value="stop blmapchill" onclick="window.location.href='server_panel.php?action=ddpp_shutdown_bl_off'"/>
    <h1 id="zone-github">Github updates</h1>
    <br/><input type="button" value="start github_update.sh" onclick="window.location.href='server_panel.php?action=start_github'"/>
    <br/><input type="button" value="update scripts/cfgs" onclick="window.location.href='server_panel.php?action=update_ddpp_scripts'"/>
<br/><br/>
    <h1 id="zone-danger">Force server restart NOW</h1>
    <h2 id="zone-danger">WARNING DANGER ZONE</h2>
    <br/><input type="button" id="btn-danger" value="restart chillerbot/srv BlmapChill" onclick="window.location.href='server_panel.php?action=restart_chillerbot_bl'"/>
    <br/><input type="button" id="btn-danger" value="restart chillerbot/srv ChillBlock5" onclick="window.location.href='server_panel.php?action=restart_chillerbot_cb'"/>
    </form>
<?php


	echo "
		</br><input type=\"button\" value=\"Logout\" onclick=\"window.location.href='logout.php'\" />
	";
}
else
{
echo "something went horrible wrong";
}
fok();
?>
