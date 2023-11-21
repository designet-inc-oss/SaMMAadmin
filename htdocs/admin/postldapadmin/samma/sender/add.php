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
 * 内部ドメイン設定登録画面
 *
 * $RCSfile: add.php,v $
 * $Revision: 1.9 $
 * $Date: 2013/08/30 06:19:52 $
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

define("OPERATION", "内部ドメイン設定追加");
define("TMPLFILE", "samma/samma_admin_sender_add.tmpl");

/***********************************************************
 * set_tag_data()
 *
 * 置き換えタグの情報をセットする
 *
 * [引数]
 *      $tag  置き換えタグの配列
 *
 * [返り値]
 *       なし
 **********************************************************/
function set_tag_data(&$tag) {
    global $sesskey;
    global $sender_name;
    global $status;

    /* ドメイン名 */
    if (isset($sender_name) === TRUE) {
        $domain = escape_html($sender_name);
    }
    $tag["<<DOMAIN>>"] = $domain;

    /* 暗号化ルール */
    $rule1 = "checked";
    $rule0 = "";

    /* 値保持暗号判定 */
    if (isset($status) === TRUE) {
        if ($status == 0){
            $rule1 = "";
            $rule0 = "checked";
        }
    }

    $tag["<<RULE_RADIO_ON>>"] = $rule1;
    $tag["<<RULE_RADIO_OFF>>"] = $rule0;

    return TRUE;

}

/*********************************************************
 * check_senderadd_dbdata
 *
 * 登録データのチェック
 *
 * [引数]
 *      $add_data       追加データ
 *      $status         ラジオボタン値
 *
 * [返り値]
 *      TRUE            正常
 *      FALSE           異常
 **********************************************************/
function check_senderadd_dbdata($add_data, $status)
{
    global $err_msg;

    /* 入力値空チェック*/
    if ($add_data == "") {
        $err_msg = "ドメイン名/メールアドレスが入力されていません。";
        return FALSE;
    }

    /* ドメイン/メールアドレス形式チェック */
    $ret = check_samma_mail($add_data);
    if ($ret === FALSE) {
        $err_msg = "ドメイン名/メールアドレスの" . $err_msg;
        return FALSE;
    }

    /* 暗号化ルール形式チェック */
    if (check_flg($status) === FALSE) {
        $err_msg = "パスワードの形式が不正です。";
        return FALSE;
    }

   return TRUE;
}

/*********************************************************
 * add_sender_dbdata
 *
 * データの追加
 *
 * [引数]
 *      $add_data       追加データ
 *      $status         ラジオボタン値
 *
 * [返り値]
 *      SUCCESS         正常
 *      FAIL            異常
 *      FAIL_EXIST      異常(既にデータあり)
 **********************************************************/
function add_sender_dbdata($add_data, $status)
{
    global $db_file;
    global $db_type;

    /* 登録アドレス変換 */
    if ($status == 0) {
        $domain = "!" . $add_data;
        $check_domain = $add_data;
    } else {
        $domain = $add_data;
        $check_domain = "!" . $add_data;
    }

    /* ドメイン/メールアドレスの登録 */
    $ret = db_add($db_file, $db_type, $domain, $check_domain, "");
    if ($ret !== SUCCESS) {
        return $ret;
    }

    return SUCCESS;
}

/***********************************************************
 * 初期処理
 **********************************************************/
/* 変数の初期化 */
$err_msg = "";
$sender_name = "";
$status = 1;
$del_list = "";
$tag = array();

/* POSTで渡ってきた値の代入 */
if (isset($_POST["sender_name"]) === TRUE) {
    $sender_name = strtolower($_POST["sender_name"]);
}
if (isset($_POST["status"]) === TRUE) {
    $status = $_POST["status"];
}
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* セッションキーを変数に代入 */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* 設定ファイル・タブ管理ファイル読込、セッションチェック */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* SaMMA設定ファイル読込み */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaconf"]);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * main処理
 **********************************************************/

/* DBファイルパス決定 */
$db_file = "";
$db_type = "";
if (isset($samma_conf["senderdb"]) === TRUE) {
    $files = explode(":", $samma_conf["senderdb"], 2);
    $db_type = $files[0];
    $db_file = $files[1];
} else {
    $err_msg = "DBファイルが設定されていません。";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* 登録ボタンが押されたとき */
if (isset($_POST["add"]) === TRUE) {

    /* 入力データの値チェック */
    $ret = check_senderadd_dbdata($sender_name, $status);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* ドメイン/メールアドレスの登録 */
        $ret = add_sender_dbdata($sender_name, $status);
        if ($ret === FAIL_EXIST) {
            $err_msg = "内部ドメイン設定は" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } else {
            /* 正常終了出力 */
            $err_msg =  "内部ドメイン設定を登録しました。(" . $sender_name . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* 内部ドメイン一覧画面へ */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }

/* キャンセルボタンが押されたとき */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* 内部ドメイン一覧画面へ */
    dgp_location("index.php");
    exit (0);
}

/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

/* bodyの表示処理 */
set_tag_data($tag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
