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
 * 内部ドメイン設定変更画面
 *
 * $RCSfile: mod.php,v $
 * $Revision: 1.12 $
 * $Date: 2013/08/30 06:20:25 $
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

define("OPERATION",  "内部ドメイン設定変更");
define("TMPLFILE", "samma/samma_admin_sender_mod.tmpl");

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

    /* 初期代入 */
    $domain = $sender_name;
    $rule1 = "checked";
    $rule0 = "";
    $delete_list = "";
    $old_status = 1;

    /* 暗号化ルール決定 */
    $str = explode("!", $domain, 2);
    if ($str[0] != $domain) {
        $domain = $str[1];
        $rule1 = "";
        $rule0 = "checked";
        $old_status = 0;
    }

    /* エスケープ */
    $domain = escape_html($domain);

    $tag["<<DOMAIN>>"] = $domain;
    $tag["<<SENDER_NAME>>"] = $sender_name;
    $tag["<<OLD_STATUS>>"] = $old_status;
    $tag["<<RULE_RADIO_ON>>"] = $rule1;
    $tag["<<RULE_RADIO_OFF>>"] = $rule0;

    return TRUE;
}

/*********************************************************
 * check_sendermod_dbdata
 *
 * 変更データのチェック
 *
 * [引数]
 *      $mod_data       変更データ(参照渡し)
 *      $status         ラジオボタン値
 *
 * [返り値]
 *      TRUE            正常
 *      FALSE           異常
 **********************************************************/
function check_sendermod_dbdata(&$mod_data, $status)
{
    global $err_msg;

    /* 内部ドメイン値変更 */
    $str = explode("!", $mod_data, 2);
    if ($str[0] != $mod_data) {
        $mod_data = $str[1];
    }

    /* 暗号化ルール形式チェック */
    if (check_flg($status) === FALSE) {
        $err_msg = "パスワードの形式が不正です。";
        return FALSE;
    }

    return TRUE;
}

/*********************************************************
 * mod_sender_dbdata
 *
 * データの追加
 *
 * [引数]
 *      $mod_data       追加データ
 *      $status         ラジオボタン値
 *      $old_status     変更前のラジオボタン値
 *
 * [返り値]
 *      SUCCESS         正常
 *      FAIL            異常
 *      FAIL_EXIST      異常(既にデータあり)
 **********************************************************/
function mod_sender_dbdata($mod_data, $status, $old_status)
{
    global $db_file;

    /* 変更があるか */
    if ($status == $old_status) {
        /* 変更がない場合は正常でreturnする */
        return SUCCESS;
    }

    /* メールアドレス処理(変更後) */
    if ($status == 0) {
        $new_domain = "!" . $mod_data;
    } else {
        $new_domain = $mod_data;
    }

    /* メールアドレス処理(変更前) */
    if ($old_status == 0) {
        $old_domain = "!" . $mod_data;
    } else {
        $old_domain = $mod_data;
    }

    /* ドメイン/メールアドレスの変更 */
    $ret = db_key_mod($db_file, $old_domain, $new_domain, "");
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
$status = "";
$old_status = "";
$del_list = "";
$tag = array();

/* POST値の代入 */
if (isset($_POST["sender_name"]) === TRUE) {
    $sender_name = $_POST["sender_name"];
}
if (isset($_POST["status"]) === TRUE) {
    $status = $_POST["status"];
}
if (isset($_POST["old_status"]) === TRUE) {
    $old_status = $_POST["old_status"];
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
if (isset($samma_conf["senderdb"]) === TRUE) {
    $files = explode(":", $samma_conf["senderdb"], 2);
    $db_file = $files[1];
} else {
    $err_msg = "DBファイルが設定されていません。";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display(CONTENT);
    exit (1);
}

/* 変更ボタンが押されたとき */
if (isset($_POST["mod"]) === TRUE) {

    /* 入力データの値チェック */
    $ret = check_sendermod_dbdata($sender_name, $status);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* ドメイン/メールアドレスの変更 */
        $ret = mod_sender_dbdata($sender_name, $status, $old_status);
        if ($ret === FAIL_EXIST || $ret === FAIL_NO_EXIST) {
            $err_msg = "内部ドメイン設定は" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } else {
            /* 正常終了出力 */
            $err_msg =  "内部ドメイン設定を更新しました。(" . $sender_name . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* 内部ドメイン設定一覧画面へ */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }

/* 削除ボタンが押されたとき */
} elseif (isset($_POST["del"]) === TRUE) {

    /* メールアドレス処理 */
    $list[0] = $sender_name;

    /* ドメイン/メールアドレスの削除 */
    $ret = db_del($db_file, $list);
    if ($ret === FAIL) {
        result_log(OPERATION . ":NG:" . $err_msg);
        syserr_display();
        exit (1);
    } elseif ($ret === FAIL_DEL) {
        $err_msg = "内部ドメイン設定の" . $err_msg;
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {

        /* 正常終了出力 */
        $err_msg = "内部ドメイン設定の" . $suc_msg;
        result_log(OPERATION . ":OK:" . $err_msg);

        /* 内部ドメイン設定一覧画面へ */
        dgp_location("index.php", $err_msg);
        exit (0);
    }

/* キャンセルボタンが押されたとき */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* 内部ドメイン設定一覧画面へ */
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
