<?php
set_time_limit(0);
ini_set("max_execution_time", "3600");
ignore_user_abort(true);
header('Content-Type:text/html;charset=UTF-8');
include 'simple_html_dom.php';

$id = intval(@$_GET['id']);

function getfiles($path) {
	echo '<br><br>已转换的文件：<br>';
	foreach (scandir($path) as $afile) {
		if ($afile == '.' || $afile == '..') {
			continue;
		}

		if (is_dir($path . '/' . $afile)) {
			// getfiles($path . '/' . $afile);
		} else {
			// echo $path . '/' . $afile . '<br />';
			$xml = simplexml_load_string(file_get_contents($path . '/' . $afile, true));
			// print_r($xml->File);
			echo '<a style="font-size:13px;" href="tmp/' . $afile . '">' . $xml['ListName'] . '[' . count($xml->File) . ']' . '</a>&nbsp;<a style="font-size:13px;" href="?id=' . explode(".", $afile)[0] . '&opt=d">删除</a><br>';
		}
	}
} //简单的demo,列出当前目录下所有的文件

// var_dump($id);
if ($id == 0 || !isset($_GET['id'])) {

	echo ('请输入用户ID：<input name="1" /><a target="_blank" href="javascript:window.location.href=\'index.php?id=\'+document.getElementsByName(\'1\')[0].value">GO</a>
<br>
		<br>新的一次抓取点击GO之后，若挂起，请再次点击GO查看进程。
		<br>请注意，由于过快的请求会导致虾米封禁，so请求时间可能会很长（通常300~400首/分），您可以关闭该页面，并稍后访问打开的链接查询导出状态~
<br>
格式：http://rabbit.moemee.com/xiamiconverter/index.php?id=你的ID<br>
		');
	getfiles('./tmp');
	exit();
}

if (isset($_GET['opt']) && $_GET['opt'] == 'd') {
	unlink("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".kgl");
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
$tmp = fopen("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR", "w");
fclose($tmp);

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

		$arr = range(3, 10);
		shuffle($arr);
		sleep($arr[0]);
	}
}

/////////////////////////

$myfile = fopen("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".kgl", "w");
fwrite($myfile, '<?xml version="1.0"?><List ListName="' . $usernameG . '的虾米收藏' . $contentText . '">' . $content . '</List>');
fclose($myfile);
unlink("tmp" . DIRECTORY_SEPARATOR . "" . $id . ".serverTmpARR");

header("Location:down.php?id=" . $id . "");

exit();
?>