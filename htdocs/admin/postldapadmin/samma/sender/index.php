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
 * 内部ドメイン設定一覧画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.9 $
 * $Date: 2013/08/30 06:19:16 $
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

define("TMPLFILE", "samma/samma_admin_sender_menu.tmpl");
define("OPERATION",  "内部ドメイン設定一覧");

/*********************************************************
 * print_result
 *
 * 検索結果表示処理
 *
 * [引数]
 *       なし
 *
 * [返り値]
 *       なし
 **********************************************************/
function print_result(&$looptag)
{
    global $db_data;
    global $del_list;
    global $sesskey;

    /* ドメイン数 */
    $domain_count = 0;

    /* データが無いときは空表示 */
    if (is_array($db_data) === FALSE) {
        $looptag = array();
        return;
    }

    /* キーでソートする(昇順) */
    uksort($db_data, DOMAIN_SORT);

    /* ドメイン名/メールアドレス表示処理 */
    foreach($db_data as $key => $value) {

        /* 初期代入 */
        $rule = DISP_EFFECT;
        $domain = $key;
        $checked = "";

        /* 暗号化ルール決定 */
        $str = explode("!", $key, 2);
        if ($str[0] != $key) {
            $domain = $str[1];
            $rule = DISP_INEFFECT;
        }

        /* エスケープ */
        $domain = escape_html($domain);
        $cnv_key = str_replace("'", "\'", $key);

        $looptag[$domain_count]["<<KEY>>"] = $key;
        $looptag[$domain_count]["<<DOMAIN>>"] = $domain;
        $looptag[$domain_count]["<<RULE>>"] = $rule;
        $looptag[$domain_count]["<<CNV_KEY>>"] = $cnv_key;

        $domain_count++;
    }

    return TRUE;
}

/***********************************************************
 * 初期処理
 **********************************************************/

/* 値の初期化 */
$tag = array();
$looptag = array();
$err_msg = "";
$del_list = "";

/* POSTで渡ってきた値の代入 */
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
    syserr_display();
    exit (1);
}

/* 登録ボタンが押されたとき */
if (isset($_POST["new_add"]) === TRUE) {

    /* 内部ドメイン設定追加画面へ */
    dgp_location("add.php");
    exit (0);

/* 削除ボタンが押されたとき */
} elseif (isset($_POST["del"]) === TRUE) {

    /* 削除項目入力チェック*/
    if (is_array($del_list) === FALSE) {
        $err_msg = "削除対象が選択されていません。";
    } else { 
        /* ドメイン/メールアドレスの削除 */
        $ret = db_del($db_file, $del_list);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "内部ドメイン設定の" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            if ($suc_msg != "") {
                $suc_msg = "内部ドメイン設定の" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
            }
        } else {
            /* 正常終了出力 */
            $err_msg = "内部ドメイン設定の" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
        }
    }
}

/* データベース検索 */
$ret = db_search($db_file, $db_data);
if ($ret === FAIL) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

/* 結果を取得しタグと置き換え */
print_result($looptag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, $looptag, STARTTAG, ENDTAG);
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
