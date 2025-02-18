<?php

use Chassit\Repeat\History;
use Chassit\Repeat\Video;
use Chassit\Repeat\DB;

require_once "init.php";

function getVideo($id): Video
{
    foreach (glob("files/*-$id.mp4") as $filename) {
        preg_match("/^files\/(.*)-([A-Za-z0-9_-]{11}).mp4$/", $filename, $matches);
        return new Video($matches[2], $matches[1]);
    }
    return new Video("", "");
}

function getFloatParam($key, ?float $defaultVal = null): ?float
{
    if (isset($_GET[$key]) && is_numeric($_GET[$key]))
        return floatval($_GET[$key]);
    return $defaultVal;
}

$id = "";
$start = 0;
$end = 90000;

if (isset($_GET["v"])) {
    $id = $_GET["v"];
}

if ($id !== "") {
    $res = DB::getRepeatCollection()->findOne(['name' => $id]);
    if ($res !== null) {
        $start = $res->start ?? $start;
        $end = $res->end ?? $end;
    }
}

$start = getFloatParam('s', $start);
$end = getFloatParam('e', $end);
$nsfc = $_GET["nsfc"] == "true";

$video = History::getRepeats([getVideo($id)])[0];
$file = "files/$video->name-$video->id.mp4";
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <link rel="stylesheet" href="index.css">
    <title><?= $video->name ?> - Chassit on Repeat</title>
</head>

<body>
<div id="video">
    <video id="myvideo" controls autoplay>
        <source src="<?= $file ?>" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <h1> <?= $video->name ?> </h1>
    <p id="videoloops"> Playtime:  0s </p>
    <input id="v" type="hidden" value="<?= $id ?>"/>
    <input id="start" placeholder="start" type="number" value="<?=$start == 0 ? "": $start ?>"/>
    <input id="end" placeholder="end" type="number" value="<?=$end == 90000 ? "": $end ?>"/>
    <input id="nsfc" type="checkbox" checked="<?= $nsfc ?>" value="true">
    <button onclick="sendInterval()">Update loop</button>
</div>

<div id="history">
    <?php
    History::render();
    ?>
</div>

<script>
    const myVideo = document.getElementById("myvideo");
    myVideo.volume = 0.5;
    const videoLoops = document.getElementById("videoloops");

    let start = <?= $start ?>;
    let end = <?= $end ?>;
    let playtime = 0;
    let nsfc = <?= $nsfc ?>;
    console.log(start);
    console.log(end);

    function update(t) {
        fetch("update.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                v: '<?= $id ?>',
                t: t,
//                s: start > 0 ? start : null,
//                e: end > myVideo.duration ? null : end,
            })
        }).then(async value => {
            if (!value.ok)
                console.log(value.status, value.statusText, await value.text());
            else{
                playtime += t;
                videoLoops.innerText = "Playtime: " + getTimeStr(playtime);
            }

        });
    }

    function sendInterval() {
        fetch("update.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                v: '<?= $id ?>',
                s: start > 0 ? start : null,
                e: end > myVideo.duration ? null : end,
                nsfc
            })
        }).then(async value => {
            if (!value.ok)
                console.log(value.status, value.statusText, await value.text());
        });
    }

    function getTimeStr(time){
        let days = Math.floor(time / 86400);
        time -= days * 86400;
        let hours = Math.floor(time / 3600);
        time -= hours * 3600;
        let minutes = Math.floor(time / 60);
        time -= minutes * 60;
        let seconds = Math.floor(time % 60);
        return `${days}d ${hours}h ${minutes}m ${seconds}s`;
    }

    document.getElementById("start").addEventListener("change",(event) =>{
        let s = event.target.value;
        if (s === "")
            start = 0;
        else {
            s = parseFloat(s);
            if(isNaN(s)) {
                return;
            }
            start = s;
        }
    });

    document.getElementById("end").addEventListener("change", (event) => {
        let e = event.target.value;
        if (e === "")
            end = 90000;
        else {
            e = parseFloat(e);
            if(isNaN(e)) {
                return;
            }
            end = e;
        }
    });

    myVideo.addEventListener('timeupdate', function () {

        if (this.currentTime < start) {
            this.currentTime = start;
            console.log("Jump Forward");
        }

        if (this.currentTime > end) {
            this.currentTime = start;
            console.log("Restart");
            myVideo.play();
            update(end - start);
        }
    }, false);

    myVideo.addEventListener('ended', function () {
        this.currentTime = start;
        myVideo.play();
        console.log("Ended");
        update(this.duration - start);
    }, false);

    myVideo.play();
</script>

<br>
</body>

</html>
