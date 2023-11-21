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
 * テンプレート編集画面
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.10 $
 * $Date: 2013/08/30 06:21:54 $
 **********************************************************/

include_once("../../initial");
include_once("lib/dglibpostldapadmin");
include_once("lib/dglibcommon");
include_once("lib/dglibpage");
include_once("lib/dglibsess");
include_once("lib/dglibldap");
include_once("lib/dglibsamma");

define("JP_ENCODE", "SJIS");
define("EN_ENCODE", "UTF-8");

/********************************************************
各ページ毎の設定
*********************************************************/

define("OPERATION",  "本文追記テンプレート編集");
define("TMPLFILE", "samma/samma_admin_addmsgtmpl.tmpl");

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
function set_tag_data(&$tag, $templ_data_jp, 
                      $templ_data_en, $templ_data_both) 
{

    global $sesskey;
    global $templ_data;

    /* htmlエスケープ */
    $tag["<<MSGADD_TMPL_DATA_JP>>"] = escape_html($templ_data_jp);
    $tag["<<MSGADD_TMPL_DATA_EN>>"] = escape_html($templ_data_en);
    $tag["<<MSGADD_TMPL_DATA_BOTH>>"] = escape_html($templ_data_both);

    return TRUE;
}

/***********************************************************
 * read_addmsg_tmpl()
 *
 * 本文追記ファイルの内容を読み込む
 *
 * [引数]
 *      &$templ_data_jp    本文追記のデータ(日本語)
 *      &$templ_data_en    本文追記のデータ(英語)
 *      &$templ_data_both  本文追記のデータ(両方)
 *
 * [返り値]
 *       なし
 **********************************************************/
function read_addmsg_tmpl(&$templ_data_jp, &$templ_data_en, &$templ_data_both)
{
    global $samma_conf;
    global $err_msg;

    /* 本文追記(日本語) */
    if (isset($samma_conf["messagetmpljppath"]) && 
        ($samma_conf["messagetmpljppath"] !== "")) {
        /* テンプレートファイルの読み込み */
        $ret = read_file($samma_conf["messagetmpljppath"], $templ_data_jp);
        if ($ret === FALSE) {
            $err_msg = "本文追記テンプレートファイル(日本語)の読み込みに失敗しました。($err_msg)";
            result_log(OPERATION . ":NG:" . $err_msg);
            return FALSE;
        }
    } else {
        $templ_data_jp = "";
    }

    /* 本文追記(英語) */
    if (isset($samma_conf["messagetmplenpath"]) &&
        ($samma_conf["messagetmplenpath"] !== "")) {
        /* テンプレートファイルの読み込み */
        $ret = read_file($samma_conf["messagetmplenpath"], $templ_data_en);
        if ($ret === FALSE) {
            $err_msg = "本文追記テンプレートファイル(英語)の読み込みに失敗しました。($err_msg)";
            result_log(OPERATION . ":NG:" . $err_msg);
            return FALSE;
        }
    } else {
        $templ_data_en = "";
    }

    /* 本文追記(両方) */
    if (isset($samma_conf["messagetmplbothpath"]) &&
        ($samma_conf["messagetmplbothpath"] !== "")) {
        /* テンプレートファイルの読み込み */
        $ret = read_file($samma_conf["messagetmplbothpath"], $templ_data_both);
        if ($ret === FALSE) {
            $err_msg = "本文追記テンプレートファイル(両方)の読み込みに失敗しました。($err_msg)";
            result_log(OPERATION . ":NG:" . $err_msg);
            return FALSE;
        }
    } else {
        $templ_data_both = "";
    }

    return TRUE;
}

/***********************************************************
 * check_input_data()
 *
 * 入力の値をチェックする 
 *
 * [引数]
 *      $templ_data_jp    本文追記のデータ(日本語)
 *      $templ_data_en    本文追記のデータ(英語)
 *      $templ_data_both  本文追記のデータ(両方)
 *
 * [返り値]
 *      FALSE    エラーがある
 *      TRUE     正常
 **********************************************************/
function check_input_data($templ_data_jp, $templ_data_en, $templ_data_both)
{
    global $samma_conf;
    global $err_msg;

    /* 本文追記(日本語) */
    if (!isset($samma_conf["messagetmpljppath"]) && $templ_data_jp !== "") {
        $err_msg = "本文追記(日本語)ファイルパスが設定しません。";
        return FALSE;
    }

    /* 本文追記(英語) */
    if (!isset($samma_conf["messagetmplenpath"]) && $templ_data_en !== "") {
        $err_msg = "文追記(英語)ファイルパスが設定しません。";
        return FALSE;
    }

    /* 本文追記(両方) */
    if (!isset($samma_conf["messagetmplbothpath"]) && $templ_data_both !== "") {
        $err_msg = "文追記(両方)ファイルパスが設定しません。";
        return FALSE;
    }

    return TRUE;
}

/***********************************************************
 * write_data_to_file()
 *
 * 本文追記テンプレートファイルに書き換える
 *
 * [引数]
 *      $templ_data_jp    本文追記のデータ(日本語)
 *      $templ_data_en    本文追記のデータ(英語)
 *      $templ_data_both  本文追記のデータ(両方)
 *
 * [返り値]
 *      FALSE    エラーがある
 *      TRUE     正常
 **********************************************************/
function write_data_to_file($templ_data_jp, $templ_data_en, $templ_data_both)
{
    global $samma_conf;
    global $err_msg;

    /* 本文追記(日本語) */
    if (isset($samma_conf["messagetmpljppath"])) {
        $ret = write_file($samma_conf["messagetmpljppath"], JP_ENCODE, $templ_data_jp);
        if ($ret === FALSE) {
            $err_msg = "本文追記テンプレートファイル(日本語)の書き込むに失敗しました。($err_msg)";
            return FALSE;
        }
    }

    /* 本文追記(英語) */
    if (isset($samma_conf["messagetmplenpath"])) {
        $ret = write_file($samma_conf["messagetmplenpath"], EN_ENCODE, $templ_data_en);
        if ($ret === FALSE) {
            $err_msg = "本文追記テンプレートファイル(英語)の書き込むに失敗しました。($err_msg)";
            return FALSE;
        }
    }

    /* 本文追記(両方) */
    if (isset($samma_conf["messagetmplbothpath"])) {
        $ret = write_file($samma_conf["messagetmplbothpath"], JP_ENCODE, $templ_data_both);
        if ($ret === FALSE) {
            $err_msg = "本文追記テンプレートファイル(両方)の書き込むに失敗しました。($err_msg)";
            return FALSE;
        }
    }

    return TRUE;
}

/***********************************************************
 * 初期処理
 **********************************************************/
/* 値の初期化 */
$err_msg = "";
$templ_data_jp = "";
$templ_data_en = "";
$templ_data_both = "";
$tag = array();
$arr_errmsg = array();

/* POSTの値を代入 */
if (isset($_POST["templ_data_jp"]) === TRUE) {
    $templ_data_jp = $_POST["templ_data_jp"];
}

if (isset($_POST["templ_data_jp"]) === TRUE) {
    $templ_data_en = $_POST["templ_data_en"];
}

if (isset($_POST["templ_data_jp"]) === TRUE) {
    $templ_data_both = $_POST["templ_data_both"];
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

/* ファイルパスが未設定の場合 */
/* 本文追記(日本語) */
if (!isset($samma_conf["messagetmpljppath"]) ||
        ($samma_conf["messagetmpljppath"] === "")) {
    array_push($arr_errmsg, "設定ファイル編集画面で本文追記のファイルパス(日本語)を設定してください。");
}

/* 本文追記(英語) */
if (!isset($samma_conf["messagetmplenpath"]) ||
        ($samma_conf["messagetmplenpath"] === "")) {
    array_push($arr_errmsg, "設定ファイル編集画面で本文追記のファイルパス(英語)を設定してください。");
}

/* 本文追記(両方) */
if (!isset($samma_conf["messagetmplbothpath"]) ||
    ($samma_conf["messagetmplbothpath"] === "")) {
    array_push($arr_errmsg, "設定ファイル編集画面で本文追記のファイルパス(両方)を設定してください。");
}

/* ファイルパスが未設定の場合 */
if (count($arr_errmsg) >= 1) {
    $err_msg = implode("<br>", $arr_errmsg);
    $log_msg = implode(", ", $arr_errmsg);
    result_log(OPERATION . ":NG:" . $log_msg);

    /* 共通のタグ設定 */
    set_tag_common($tag);

    set_tag_data($tag, "", "", "");

    /* ページの出力 */
    $ret = display(TMPLFILE, $tag, array(), "", "");
    if ($ret === FALSE) {
        result_log($log_msg, LOG_ERR);
        syserr_display();
        exit(1);
    }

    exit(0);
}

/* commandpass退避 */
if (isset($samma_conf["commandpass"]) === TRUE) {
    $commandpass = $samma_conf["commandpass"];
}

/***********************************************************
 * main処理
 **********************************************************/

/* 変更ボタンが押されたとき */
if (isset($_POST["mod"]) === TRUE) {

    /* 本文追記(日本語) */
    $ret = check_input_data($templ_data_jp, $templ_data_en, $templ_data_both);

    /* 入力チェック */
    if (!$ret) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        
        /* テンプレートファイルの書き換え */
        $ret = write_data_to_file($templ_data_jp, $templ_data_en, $templ_data_both);

        if ($ret === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        } else {
            /* sammaテンプレートのリロード */
            $ret = reload_samma(ADDMSG);
            if ($ret === FALSE) {
                result_log(OPERATION . ":NG:" . $err_msg);
            } else {
                /* 正常終了出力 */
                $err_msg = "定型文ファイルを更新しました。";
                result_log(OPERATION . ":OK:" . $err_msg);

                /* SaMMA管理メニュー画面へ */
                dgp_location("../index.php", $err_msg);
                exit(0);
            }
        }
    }

/* キャンセルボタンが押されたとき */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* SaMMA管理メニュー画面へ */
    dgp_location("../index.php", $err_msg);
    exit(0);
}

/* 初期表示 */
if (isset($_POST["mod"]) === FALSE) {

    /* テンプレートファイルの読み込み */
    $ret = read_addmsg_tmpl($templ_data_jp, $templ_data_en, $templ_data_both);
    if (!$ret) {
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

set_tag_data($tag, $templ_data_jp, $templ_data_en, $templ_data_both);

/* ページの出力 */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
