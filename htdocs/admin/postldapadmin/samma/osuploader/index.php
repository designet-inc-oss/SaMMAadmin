<?php

/*
 * postLDAPadmin
 *
 * Copyright (C) 2006,2007 DesigNET, INC.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

/***********************************************************
 * オンラインストレージ連携設定画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 5.00 $
 * $Date: 2021/05/26 10:10:10 $
 **********************************************************/

include_once("../../initial");
include_once("lib/dglibpostldapadmin");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibldap");
include_once("lib/dglibsamma");

/********************************************************
各ページ毎の設定
*********************************************************/

define("OPERATION", "オンラインストレージ連携設定");
define("TMPLFILE", "samma/samma_admin_osuploader.tmpl");

/*********************************************************
 * check_tmpl
 *
 * テンプレートファイルチェック
 *
 * [引数]
 *       $filename      テンプレートファイル
 *
 * [返り値]
 *      TRUE            正常
 *      FALSE           異常
 **********************************************************/
function check_tmpl($filename)
{
    global $err_msg;

    /* 形式チェック　*/
    $ret = check_file($filename);
    if ($ret === FAIL) {
        $err_msg = "テンプレート" . $err_msg;
        return FALSE;
    /* ファイルがない場合は作成 */
    } elseif ($ret === FAIL_NO_EXIST) {
        /* ディレクトリ書き込み権チェック */
        if (is_writable(dirname($filename)) !== TRUE) {
            $err_msg = "ディレクトリに書込み権がありません。(" . $filename . ")";
            return FALSE;
        }
    }

    return TRUE;
}

/***********************************************************
 * display_result()
 *
 * 置き換えタグの情報をセットする
 *
 * [引数]
 *      $tag  置き換えタグの配列
 *
 * [返り値]
 *       なし
 **********************************************************/
function display_result(&$tag) 
{
    global $sesskey;
    global $samma_conf;
    global $logfacility;
    global $web_conf; 

    /* NextCloudのAPIのトップのURL */
    $nc_url = "";
    if (isset($samma_conf["nc_url"]) === TRUE && 
              $samma_conf["nc_url"] != "") {
        $nc_url = escape_html($samma_conf["nc_url"]);
    }
    $tag["<<NC_URL>>"] = $nc_url;

    /* NextCloudの管理者ユーザID */
    $nc_admin = "";
    if (isset($samma_conf["nc_admin"]) === TRUE && 
              $samma_conf["nc_admin"] != "") {
        $nc_admin = escape_html($samma_conf["nc_admin"]);
    }
    $tag["<<NC_ADMIN>>"] = $nc_admin;

    /* NextCloudの管理者パスワード */
    $nc_adminpw = "";
    if (isset($samma_conf["nc_adminpw"]) === TRUE && 
              $samma_conf["nc_adminpw"] != "nc_adminpw") {
        $nc_adminpw = escape_html($samma_conf["nc_adminpw"]);
    }
    $tag["<<NC_ADMINPW>>"] = $nc_adminpw;

    /* NextCloudへのアクセスタイムアウト */
    $nc_timeout = "";
    if (isset($samma_conf["nc_timeout"]) === TRUE && 
              $samma_conf["nc_timeout"] != "") {
        $nc_timeout = escape_html($samma_conf["nc_timeout"]);
    }
    $tag["<<NC_TIMEOUT>>"] = $nc_timeout;

    /* HTTPS証明書 */
    $https_cert = "";
    if (isset($samma_conf["https_cert"]) === TRUE && 
              $samma_conf["https_cert"] != "") {
        $https_cert = escape_html($samma_conf["https_cert"]);
    }
    $tag["<<HTTPS_CERT>>"] = $https_cert;

    /* テンプレートファイル名 */
    $template_file = "";
    if (isset($samma_conf["template_file"]) === TRUE && 
              $samma_conf["template_file"] != "") {
        $template_file = escape_html($samma_conf["template_file"]);
    }
    $tag["<<TEMPLATE_FILE>>"] = $template_file;

    /* StrCode設定 */
    $str_code = "";
    if (isset($samma_conf["str_code"]) === TRUE && 
              $samma_conf["str_code"] != "") {
        $str_code = escape_html($samma_conf["str_code"]);
    }
    $tag["<<STR_CODE>>"] = $str_code;

    /* 1セッションで並列にNextCloudにアップロードファイル数 */
    $concurrent = "";
    if (isset($samma_conf["concurrent"]) === TRUE &&
              $samma_conf["concurrent"] != "") {
        $concurrent = escape_html($samma_conf["concurrent"]);
    }
    $tag["<<CONCURRENT>>"] = $concurrent;

    /* NextCloudから通知された共有URLのプロトコルを強制的にhttpsに変更するフラグ */
    $flag_on = "";
    $flag_off = "checked";
    if (isset($samma_conf["force_https"]) === TRUE && 
              $samma_conf["force_https"] != "") {
        if (strtolower($samma_conf["force_https"]) === "true") {
            $flag_on = "checked";
            $flag_off = "";
        }
    }
    $tag["<<FORCE_HTTPS_ON>>"] = $flag_on;
    $tag["<<FORCE_HTTPS_OFF>>"] = $flag_off;

    return TRUE;
}

/*********************************************************
 * check_conf_data
 *
 * 設定ファイルデータチェック
 *
 * [引数]
 *       $data		設定ファイルデータ
 *
 * [返り値]
 *	TRUE		正常
 *	FALSE		異常
 **********************************************************/
function check_conf_data(&$data)
{
    global $err_msg;
    global $logfacility;
    global $str_code;
    global $samma_conf;

    /*
     * 必須項目のチェック
     */
    /* NextCloudのAPIのトップのURL */
    if ($data["nc_url"] === "") {
        $err_msg = "NextCloudのAPIのトップのURLが入力されていません。";
        return FALSE;
    }

    /* NextCloudの管理者ユーザID */
    if ($data["nc_admin"] === "") {
        $err_msg = "NextCloudの管理者ユーザIDが入力されていません。";
        return FALSE;
    }
    /* NextCloudの管理者パスワード */
    if ($data["nc_adminpw"] === "") {
        $err_msg = "NextCloudの管理者パスワードが入力されていません。";
        return FALSE;
    }

    /*
     * 形式チェック
     */

    /* NextCloudへのアクセスタイムアウト */
    if ($data["nc_timeout"] !== "") {
        if (check_integer($data["nc_timeout"]) === FALSE) {
            $err_msg = "NextCloudへのアクセスタイムアウトは1以上の整数を設定してください。";
            return FALSE;
        }
    }

    /* テンプレートファイル名 */
    if ($data["template_file"] !== "") {
        /* 形式チェック(なければ作成) */
        if (check_tmpl($data["template_file"]) === FALSE) {
            return FALSE;
        }
    }

    /* 1セッションで並列にNextCloudにアップロードファイル数 */
    if ($data["concurrent"] !== "") {
        if (check_integer($data["concurrent"]) === FALSE) {
            $err_msg = "1セッションで並列にNextCloudにアップロードファイル数は1以上の整数を設定してください。";
            return FALSE;
        }
    }

    return TRUE;
}

/*********************************************************
 * check_messagetmplpath
 *
 * 本文追記のファイルパスの入力値をチェックする
 * ファイルを存在しない場合、空ファイルを作成する
 * 
 * [引数]
 *       $jp_path       本文追記のファイルパス(日本語)
 *       $en_path       本文追記のファイルパス(英語)
 *       $both_path     本文追記のファイルパス(両方)
 *       &$errormsg     エラーメッセージ(参照データ)
 *
 * [返り値]
 *      TRUE            正常
 *      FALSE           異常
 **********************************************************/
function check_messagetmplpath($jp_path, $en_path, $both_path, &$errormsg)
{
    if (strlen($jp_path) > 256) {
        $errormsg = "本文追記のファイルパス(日本語)が長すぎです。($jp_path)";
        return FALSE;
    }

    if (strlen($en_path) > 256) {
        $errormsg = "本文追記のファイルパス(英語)が長すぎです。($en_path)";
        return FALSE;
    }

    if (strlen($both_path) > 256) {
        $errormsg = "本文追記のファイルパス(両方)が長すぎです。($both_path)";
        return FALSE;
    }

    if ($jp_path !== "") {
        /* ファイルの存在をチェック */
        if (!file_exists($jp_path)) {
            /* ファイルを作成 */
            if (touch($jp_path) === FALSE) { 
                $errormsg = "本文追記のファイル(日本語)の作成に失敗しました。($jp_path)";
                return FALSE;
            }
        }
       /* 通常ファイルかどうかを調べる */
       if(!is_file($jp_path)) {
            $errormsg = "本文追記のファイル(日本語)は通常ファイルではありません。($jp_path)";
            return FALSE;
       }
    }

    if ($en_path !== "") {
        /* ファイルの存在をチェック */
        if (!file_exists($en_path)) {
            /* ファイルを作成 */
            if (!touch($en_path)) {
                $errormsg = "本文追記のファイル(英語)の作成に失敗しました。($en_path)";
                return FALSE;
            }
        }
       /* 通常ファイルかどうかを調べる */
       if(!is_file($en_path)) {
            $errormsg = "本文追記のファイル(日本語)は通常ファイルではありません。($en_path)";
            return FALSE;
       }
    }

    if ($both_path) {
        /* ファイルの存在をチェック */
        if (!file_exists($both_path)) {
            /* ファイルを作成 */
            if (!touch($both_path)) {
                $errormsg = "本文追記のファイル(両方)の作成に失敗しました。($both_path)";
                return FALSE;
            }
        }
        /* 通常ファイルかどうかを調べる */
        if(!is_file($both_path)) {
            $errormsg = "本文追記のファイル(日本語)は通常ファイルではありません。($both_path)";
            return FALSE;
        }
    }

    return TRUE;
}

/*********************************************************
 * check_db
 *
 * データベースファイルチェック
 *
 * [引数]
 *       $db_file	DBファイル
 *	 $db_type	DB形式
 *
 * [返り値]
 *	TRUE		正常
 *	FALSE		異常
 **********************************************************/
function check_db($db_file, $db_type)
{
    global $err_msg;

    /* 形式チェック　*/
    $ret = check_file($db_file);
    if ($ret === FAIL) {
        $err_msg = "DB" . $err_msg;
        return FALSE;
    /* DBファイルがない場合は作成 */
    } elseif ($ret === FAIL_NO_EXIST) {
        /* 空DB作成 */
        $type = 0;
        if ($db_type == "btree") {
            $type = 1;
        }
        if (make_db($db_file, $type) === FALSE) {
            $err_msg = "DB用の" . $err_msg;
            return FALSE;
        }
    /* チェックOKな場合は形式チェック */
    } else {
        $command = sprintf(CONFIRM_DB, $db_type, escapeshellcmd($db_file));
        $ret = system($command, $result);

        if ($result != 0) {
            $err_msg = "DB形式が不正です。";
            return FALSE;
        }
        if ($ret === FALSE){
            $err_msg = "DB形式の確認に失敗しました。";
            return FALSE;
        }
    }
    return TRUE;

}

/*********************************************************
 * set_disp_data
 *
 * 値を表示用配列にセットします
 *
 * [引数]
 *       $data		データ
 *	 $disp_data	データ
 *
 * [返り値]
 *	なし
 **********************************************************/
function set_disp_data($data, &$disp_data)
{

    /* NextCloudのAPIのトップのURL */
    if (isset($data["nc_url"]) === TRUE) {
        $disp_data["nc_url"] = $data["nc_url"];
    }

    /* NextCloudの管理者ユーザID */
    if (isset($data["nc_admin"]) === TRUE) {
        $disp_data["nc_admin"] = $data["nc_admin"];
    }

    /* NextCloudの管理者パスワード */
    if (isset($data["nc_adminpw"]) === TRUE) {
        $disp_data["nc_adminpw"] = $data["nc_adminpw"];
    }

    /* NextCloudへのアクセスタイムアウト */
    if (isset($data["nc_timeout"]) === TRUE) {
        $disp_data["nc_timeout"] = $data["nc_timeout"];
    }

    /* HTTPS証明書 */
    if (isset($data["https_cert"]) === TRUE) {
        $disp_data["https_cert"] = $data["https_cert"];
    }

    /* テンプレートファイル名 */
    if (isset($data["template_file"]) === TRUE) {
        $disp_data["template_file"] = $data["template_file"];
    }

    /* StrCode設定 */
    if (isset($data["str_code"]) === TRUE) {
        $disp_data["str_code"] = $data["str_code"];
    }

    /* 1セッションで並列にNextCloudにアップロードファイル数 */
    if (isset($data["concurrent"]) === TRUE) {
        $disp_data["concurrent"] = $data["concurrent"];
    }

    /* NextCloudから通知された共有URLのプロトコルを強制的にhttpsに変更するフラグ */
    if (isset($data["force_https"]) === TRUE) {
        if (strtolower($data["force_https"]) === "true") {
            $force_https = "True";
        } else {
            $force_https = "False";
        }

        $disp_data["force_https"] = $force_https;
    }

    return;
}

/***********************************************************
 * 初期処理
 **********************************************************/
/* 値の初期化 */
$tag = array();

/* セッションキーを変数に代入 */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* 設定ファイルタブ管理ファイル読込、セッションのチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* SaMMA設定ファイル読込み */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaosuploaderconf"]);
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/
/* 処理の分岐 */

/* 更新 */
if (isset($_POST["mod"]) === TRUE) {
    /* 値保持用データ取得 */
    set_disp_data($_POST, $samma_conf);
    /* 入力チェック */
    if (check_conf_data($samma_conf) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* 更新 */
        if (mod_osuploader_conf($samma_conf) === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        /* 成功 */
        } else {
            $err_msg = "オンラインストレージの設定ファイルを更新しました。";
            result_log(OPERATION . ":OK:" . $err_msg);
           /* メニュー画面へ遷移 */
           dgp_location("../index.php", $err_msg);
           exit (0);
        }
    }

/* キャンセル */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* メニュー画面へ遷移 */
    dgp_location("../index.php", $err_msg);
    exit (0);

}

/***********************************************************
 * 表示処理
 **********************************************************/

/* 共通のタグ設定 */
set_tag_common($tag);

/* 結果を取得しタグと置き換え */
display_result($tag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
