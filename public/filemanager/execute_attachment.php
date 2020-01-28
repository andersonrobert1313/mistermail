<?php
$config = include 'config/config_attachment.php';
//TODO switch to array
extract($config, EXTR_OVERWRITE);

include 'include/utils.php';

//if ($_SESSION['RF']["verify"] != "RESPONSIVEfilemanager")
//{
//	response(trans('forbiden').AddErrorLocation())->send();
//	exit;
//}

if (strpos($_POST['path'], '/')===0
    || strpos($_POST['path'], '../')!==false
    || strpos($_POST['path'], './')===0) {
    response(trans('wrong path'.AddErrorLocation()))->send();
    exit;
}

$path= $_GET['path'];
$file= $_GET['file'];
$path = $current_path.$path.$file;
$_token=isset($_GET['_token']) ? $_GET['_token']:'';
if (!$_token) {
    return false;
}
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete_file':
            if (file_exists($path)) {
                unlink($path);
            }
        echo "File Deleted Success.";
            break;
        default:
            response(trans('wrong action').AddErrorLocation())->send();
            exit;
    }
}
