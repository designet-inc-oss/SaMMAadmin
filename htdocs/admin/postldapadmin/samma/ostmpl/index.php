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
 * オンラインストレージのテンプレート編集画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 5.00 $
 * $Date: 2021/05/27 10:10:10 $
 **********************************************************/
define("OPERATION",      "os_uploaderの通知ファイルテンプレート編集");
define("TEMPLATEPATH",   "template_file");

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
define("TMPLFILE", "samma/samma_admin_osuploadertmpl.tmpl");

/***********************************************************
 * 関数
 **********************************************************/
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
    global $templ_data;

    /* htmlエスケープ */
    $cnv_templ_data = escape_html($templ_data);
    $tag["<<TMPL_DATA>>"] = $cnv_templ_data;

    return TRUE;

}

/***********************************************************
 * 初期処理
 **********************************************************/
/* 値の初期化 */
$err_msg = "";
$templ_data = "";
$tag = array();

/* POSTの値を代入 */
if (isset($_POST["templ_data"]) === TRUE) {
    $templ_data = $_POST["templ_data"];
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

/* オンラインストレージの設定ファイルを読み込む */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaosuploaderconf"]);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* オンラインストレージの設定ファイルにTEMPLATE_FILEが未設定 */
if (!isset($samma_conf[TEMPLATEPATH])) {
    $err_msg = "オンラインストレージ連携設定画面でテンプレートファイルパスを設定してください。";
    result_log(OPERATION . ":NG:" . $err_msg);

    /* 共通のタグ設定 */
    set_tag_common($tag);

    set_tag_data($tag);

    /* ページの出力 */
    $ret = display(TMPLFILE, $tag, array(), "", "");
    if ($ret === FALSE) {
        result_log($log_msg, LOG_ERR);
        syserr_display();
        exit(1);
    }

    exit(0);
}

/***********************************************************
 * main処理
 **********************************************************/

/* 変更ボタンが押されたとき */
if (isset($_POST["mod"]) === TRUE) {

    /* 入力チェック */
    if (empty($templ_data) === TRUE) {
        $err_msg = "テキストファイルテンプレートが入力されていません。";
        result_log(OPERATION . ":OK:" . $err_msg);
    } else {
        /* テンプレートファイルの書き換え */
        $ret = write_file($samma_conf[TEMPLATEPATH], STRCODE, $templ_data);
        if ($ret === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        } else {
            /* 正常終了出力 */
            $err_msg = "オンラインストレージのテンプレートを更新しました。";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* SaMMA管理メニュー画面へ */
            dgp_location("../index.php", $err_msg);
            exit(0);
        }
    }

/* キャンセルボタンが押されたとき */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* SaMMA管理メニュー画面へ */
    dgp_location("../index.php", $err_msg);
    exit(0);
}

/* 初期表示 */
if (isset($_POST["templ_data"]) === FALSE) {

    /* テンプレートファイルの読み込み */
    $ret = read_file($samma_conf[TEMPLATEPATH], $templ_data);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
        syserr_display();
        exit (1);
    }
}

/***********************************************************
 * 表示処理
 **********************************************************/
/* 共通のタグ設定 */
set_tag_common($tag);

set_tag_data($tag);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
