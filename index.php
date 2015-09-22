<?php

$secretKey = 'MyriaIsBaka';
set_time_limit(0);
ini_set("max_execution_time", "3600");
ignore_user_abort(true);
header('Content-Type:text/html;charset=UTF-8');
echo '<title>虾米转换器 v1.1</title>';
include 'simple_html_dom.php';
$id = intval(@$_GET['id']);

function get_extension($file) {
	substr(strrchr($file, '.'), 1);
}

function getfiles($path) {
	global $secretKey;
	echo '<br><br>已转换的文件：<br>';
	foreach (scandir($path) as $afile) {
		if ($afile == '.' || $afile == '..') {
			continue;
		}

		if (is_dir($path . '/' . $afile)) {
			// getfiles($path . '/' . $afile);
		} else {
			if (strrchr($afile, '.') == '.kgl') {
				// echo $path . '/' . $afile . '<br />';
				$xml = simplexml_load_string(file_get_contents($path . '/' . $afile, true));
				// print_r($xml->File);
				echo '<a style="font-size:13px;" href="down.php?id=' . $afile . '">' . $xml['ListName'] . '[' . count($xml->File) . ']' . '</a>&nbsp;';

				if (isset($_GET['key']) && @$_GET['key'] == $secretKey) {
					echo '<a style="font-size:13px;" href="?id=' . explode(".", $afile)[0] . '&opt=d&key=' . $secretKey . '">删除</a><br>';
				} else {
					echo '<br>';
				}

			}

		}
	}
} //简单的demo,列出当前目录下所有的文件

// var_dump($id);
if ($id == 0 || !isset($_GET['id'])) {

	echo ('请输入用户ID：<input name="1" />&nbsp;<input type="button" value="Start" onclick="javascript:window.location.href=\'index.php?id=\'+document.getElementsByName(\'1\')[0].value">
<br>
		<br>新的一次抓取点击Start之后，若挂起，请再次点击GO查看进程。
<br>
		');

	if (file_exists("tmp" . DIRECTORY_SEPARATOR . "queue.queue")) {
		echo '<br><br>当前队列：<br>';
		$farray = file("tmp" . DIRECTORY_SEPARATOR . "queue.queue");
		foreach ($farray as $key => $value) {
			# code...
			if ($key < 1) {
				if (count($farray) == 0) {
					echo '<a style="font-size:13px;">' . trim($value) . '&nbsp;载入中</a><br>';
				} else {
					echo '<a style="font-size:13px;">' . trim($value) . '&nbsp;' . trim(file("tmp" . DIRECTORY_SEPARATOR . "" . (int) $value . ".serverTmpARR")[0]) . '</a><br>';
				}

			} else {
				echo '<a style="font-size:13px;">' . trim($value) . '&nbsp;排队中</a><br>';
			}
		}
	}

	getfiles('./tmp');
	echo '<br><br>版本历史：<br>
<a style="font-size:13px;">1.1 增加队列功能，大幅度减少当前任务抓取时间</a>
	';
	exit();
}

if (isset($_GET['opt']) && $_GET['opt'] == 'd') {
	if (isset($_GET['key']) && @$_GET['key'] == $secretKey) {
		@unlink("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".kgl");
		header('location:index.php?key=' . $secretKey . '');
	}

	header('location:index.php');
	exit();
}
if (file_exists("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR")) {
	// $tmp = fread(, "r");
	$content = file_get_contents("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR");
	// var_dump($content);
	echo $content . '<br>';
	// fclose($tmp);
	exit('该用户的转换已经进入队列，请稍后再试');
}

if (file_exists("tmp/" . $id . ".kgl")) {
	exit('发现已经转换过的文件<a href="down.php?id=' . $id . '">下载</a>');
}

foreach (scandir("tmp") as $afile) {
	if ($afile == '.' || $afile == '..') {
		continue;
	}

	if (is_dir("tmp" . '/' . $afile)) {
		// getfiles($path . '/' . $afile);

	} else {
		// echo strrchr($afile, '.');
		if (strrchr($afile, '.') == '.serverTmpARR') {
			// echo $path . '/' . $afile . '<br />';
			// 检查到除自己外还有别的转换任务
			// 添加队列
			$queue = fopen("tmp" . DIRECTORY_SEPARATOR . "queue.queue", "a+");
			// echo fwrite($queue, "Hello World. Testing!");

			// 判断是否添加过
			if (!file_exists("tmp" . DIRECTORY_SEPARATOR . "queue.queue")) {
				exit('访问过快，内部错误');
			}
			if (preg_match("/\b" . $id . "\b/i", file_get_contents("tmp" . DIRECTORY_SEPARATOR . "queue.queue")) == 0) {
				if (filesize("tmp" . DIRECTORY_SEPARATOR . "queue.queue") == 0) {
					fwrite($queue, $id);
				} else {
					fwrite($queue, "\n" . $id);
				}
				exit('成功添加到抓取队列');
			} else {

				exit('您已经提交过，请不要重复提交，耐心等待队列完成');
			}

			break;
		}

	}
}

// exit();

function GET($id = '') {

	$tmp = fopen("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR", "w");
	$queue = fopen("tmp" . DIRECTORY_SEPARATOR . "queue.queue", "w");
	fwrite($queue, $id);
	fclose($tmp);
	fclose($queue);

	$html = file_get_html('http://www.xiami.com/space/lib-song/u/' . $id . '/page/1');
	// $html = file_get_html('http://baidu.com/');
	// var_dump($html);
	// var_dump($html);
	if (!$html) {
		exit('服务器已经被屏蔽，请稍后重试= =||||');
	}
	$num = $html->find('span.counts');
	$username = $html->find('a.personal_iconX');
	$usernameG = ''; // 全局用户名变量存储
	$numG = ''; // 全局数量变量存储
	// 输出用户名
	foreach ($username as $element) {
		// echo '用户：' . $element->title . '<br>';
		$usernameG = $element->title;
	}
	// 输出收藏的数量
	foreach ($num as $element) {
		echo '收藏的数量：' . intval($element->plaintext) . '<br>';
		$numG = intval($element->plaintext);
	}

	$page = (int) ($numG / 25 + 1);

	if ((int) ($page) == 0) {
		exit('获取失败，地址不合法或者用户没有收藏歌曲');
	}

	$content = ''; // XML字段
	$contentText = '';
	/////////////////////////////////////////////////////////
	for ($i = 1; $i <= $page; $i++) {
		$html_page = file_get_html('http://www.xiami.com/space/lib-song/u/' . $id . '/page/' . $i . '');
		if (!$html_page) {
			$contentText = '（不完整的）';
			$tmp = fopen("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR", "w");
			fwrite($tmp, '失败，转换过程中被虾米封杀，稍后重试');
			fclose($tmp);
			break;
		} else {
			$table = $html_page->find('table.track_list tbody tr');
			foreach ($table as $element) {
				// echo $element->children(1)->children(0)->title . '－' . $element->children(1)->children(1)->title . '<br>';
				$c1 = htmlspecialchars_decode($element->children(1)->children(0)->title, ENT_QUOTES);
				$c2 = htmlspecialchars_decode($element->children(1)->children(1)->title, ENT_QUOTES);
				$c1 = preg_replace('/&nbsp;/', '', $c1);
				$c2 = preg_replace('/&nbsp;/', '', $c2);

				$c1 = preg_replace('/</', '(', $c1);
				$c1 = preg_replace('/>/', ')', $c1);
				$c2 = preg_replace('/</', '(', $c2);
				$c2 = preg_replace('/</', ')', $c2);

				$c1 = preg_replace('/&/', ' ', $c1);
				$c2 = preg_replace('/&/', ' ', $c2);
				// (?!\s)2(?!\s)

				$content .= '<File><FileName>' . $c2 . ' - ' . $c1 . '.mp3</FileName></File>';
			}

			$tmp = fopen("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR", "w");
			fwrite($tmp, '' . $usernameG . ' 的收藏列表抓取进度 ' . $i . '/' . $page . '');
			fclose($tmp);

			// 线程阻塞
			// $arr = range(3, 10);
			// shuffle($arr);
			// sleep($arr[0]);
			// sleep(3);
		}
	}

	/////////////////////////
	// 生成文件
	$myfile = fopen("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".kgl", "w");
	fwrite($myfile, '<?xml version="1.0"?><List ListName="' . $usernameG . '的虾米收藏' . $contentText . '">' . $content . '</List>');
	fclose($myfile);

	// 删除零时文件和队列文件
	$farray = file("tmp" . DIRECTORY_SEPARATOR . "queue.queue"); //读取文件数据到数组中
	$queue = fopen("tmp" . DIRECTORY_SEPARATOR . "queue.queue", "w");
	array_shift($farray);
	if (count($farray) < 1) {
		// 删除队列文件
		@unlink("tmp" . DIRECTORY_SEPARATOR . "queue.queue");
		@unlink("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR");
		return false;
	} else {
		foreach ($farray as $key => $value) {
			if (count($farray) == 1) {
				fwrite($queue, $value);
			} else {
				fwrite($queue, "\n" . $value);
			}
		}
	}

	@unlink("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR");
	GET($farray[0]);
	// header("Location:down.php?id=" . $id . "");
}
GET($id);
@unlink("tmp" . DIRECTORY_SEPARATOR . "queue.queue");
exit();
?>