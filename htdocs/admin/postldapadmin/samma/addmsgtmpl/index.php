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
 * �ƥ�ץ졼���Խ�����
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
�ƥڡ����������
*********************************************************/

define("OPERATION",  "��ʸ�ɵ��ƥ�ץ졼���Խ�");
define("TMPLFILE", "samma/samma_admin_addmsgtmpl.tmpl");

/***********************************************************
 * �ؿ�
 **********************************************************/
/***********************************************************
 * set_tag_data()
 *
 * �֤����������ξ���򥻥åȤ���
 *
 * [����]
 *      $tag  �֤���������������
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function set_tag_data(&$tag, $templ_data_jp, 
                      $templ_data_en, $templ_data_both) 
{

    global $sesskey;
    global $templ_data;

    /* html���������� */
    $tag["<<MSGADD_TMPL_DATA_JP>>"] = escape_html($templ_data_jp);
    $tag["<<MSGADD_TMPL_DATA_EN>>"] = escape_html($templ_data_en);
    $tag["<<MSGADD_TMPL_DATA_BOTH>>"] = escape_html($templ_data_both);

    return TRUE;
}

/***********************************************************
 * read_addmsg_tmpl()
 *
 * ��ʸ�ɵ��ե���������Ƥ��ɤ߹���
 *
 * [����]
 *      &$templ_data_jp    ��ʸ�ɵ��Υǡ���(���ܸ�)
 *      &$templ_data_en    ��ʸ�ɵ��Υǡ���(�Ѹ�)
 *      &$templ_data_both  ��ʸ�ɵ��Υǡ���(ξ��)
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function read_addmsg_tmpl(&$templ_data_jp, &$templ_data_en, &$templ_data_both)
{
    global $samma_conf;
    global $err_msg;

    /* ��ʸ�ɵ�(���ܸ�) */
    if (isset($samma_conf["messagetmpljppath"]) && 
        ($samma_conf["messagetmpljppath"] !== "")) {
        /* �ƥ�ץ졼�ȥե�������ɤ߹��� */
        $ret = read_file($samma_conf["messagetmpljppath"], $templ_data_jp);
        if ($ret === FALSE) {
            $err_msg = "��ʸ�ɵ��ƥ�ץ졼�ȥե�����(���ܸ�)���ɤ߹��ߤ˼��Ԥ��ޤ�����($err_msg)";
            result_log(OPERATION . ":NG:" . $err_msg);
            return FALSE;
        }
    } else {
        $templ_data_jp = "";
    }

    /* ��ʸ�ɵ�(�Ѹ�) */
    if (isset($samma_conf["messagetmplenpath"]) &&
        ($samma_conf["messagetmplenpath"] !== "")) {
        /* �ƥ�ץ졼�ȥե�������ɤ߹��� */
        $ret = read_file($samma_conf["messagetmplenpath"], $templ_data_en);
        if ($ret === FALSE) {
            $err_msg = "��ʸ�ɵ��ƥ�ץ졼�ȥե�����(�Ѹ�)���ɤ߹��ߤ˼��Ԥ��ޤ�����($err_msg)";
            result_log(OPERATION . ":NG:" . $err_msg);
            return FALSE;
        }
    } else {
        $templ_data_en = "";
    }

    /* ��ʸ�ɵ�(ξ��) */
    if (isset($samma_conf["messagetmplbothpath"]) &&
        ($samma_conf["messagetmplbothpath"] !== "")) {
        /* �ƥ�ץ졼�ȥե�������ɤ߹��� */
        $ret = read_file($samma_conf["messagetmplbothpath"], $templ_data_both);
        if ($ret === FALSE) {
            $err_msg = "��ʸ�ɵ��ƥ�ץ졼�ȥե�����(ξ��)���ɤ߹��ߤ˼��Ԥ��ޤ�����($err_msg)";
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
 * ���Ϥ��ͤ�����å����� 
 *
 * [����]
 *      $templ_data_jp    ��ʸ�ɵ��Υǡ���(���ܸ�)
 *      $templ_data_en    ��ʸ�ɵ��Υǡ���(�Ѹ�)
 *      $templ_data_both  ��ʸ�ɵ��Υǡ���(ξ��)
 *
 * [�֤���]
 *      FALSE    ���顼������
 *      TRUE     ����
 **********************************************************/
function check_input_data($templ_data_jp, $templ_data_en, $templ_data_both)
{
    global $samma_conf;
    global $err_msg;

    /* ��ʸ�ɵ�(���ܸ�) */
    if (!isset($samma_conf["messagetmpljppath"]) && $templ_data_jp !== "") {
        $err_msg = "��ʸ�ɵ�(���ܸ�)�ե�����ѥ������ꤷ�ޤ���";
        return FALSE;
    }

    /* ��ʸ�ɵ�(�Ѹ�) */
    if (!isset($samma_conf["messagetmplenpath"]) && $templ_data_en !== "") {
        $err_msg = "ʸ�ɵ�(�Ѹ�)�ե�����ѥ������ꤷ�ޤ���";
        return FALSE;
    }

    /* ��ʸ�ɵ�(ξ��) */
    if (!isset($samma_conf["messagetmplbothpath"]) && $templ_data_both !== "") {
        $err_msg = "ʸ�ɵ�(ξ��)�ե�����ѥ������ꤷ�ޤ���";
        return FALSE;
    }

    return TRUE;
}

/***********************************************************
 * write_data_to_file()
 *
 * ��ʸ�ɵ��ƥ�ץ졼�ȥե�����˽񤭴�����
 *
 * [����]
 *      $templ_data_jp    ��ʸ�ɵ��Υǡ���(���ܸ�)
 *      $templ_data_en    ��ʸ�ɵ��Υǡ���(�Ѹ�)
 *      $templ_data_both  ��ʸ�ɵ��Υǡ���(ξ��)
 *
 * [�֤���]
 *      FALSE    ���顼������
 *      TRUE     ����
 **********************************************************/
function write_data_to_file($templ_data_jp, $templ_data_en, $templ_data_both)
{
    global $samma_conf;
    global $err_msg;

    /* ��ʸ�ɵ�(���ܸ�) */
    if (isset($samma_conf["messagetmpljppath"])) {
        $ret = write_file($samma_conf["messagetmpljppath"], JP_ENCODE, $templ_data_jp);
        if ($ret === FALSE) {
            $err_msg = "��ʸ�ɵ��ƥ�ץ졼�ȥե�����(���ܸ�)�ν񤭹���˼��Ԥ��ޤ�����($err_msg)";
            return FALSE;
        }
    }

    /* ��ʸ�ɵ�(�Ѹ�) */
    if (isset($samma_conf["messagetmplenpath"])) {
        $ret = write_file($samma_conf["messagetmplenpath"], EN_ENCODE, $templ_data_en);
        if ($ret === FALSE) {
            $err_msg = "��ʸ�ɵ��ƥ�ץ졼�ȥե�����(�Ѹ�)�ν񤭹���˼��Ԥ��ޤ�����($err_msg)";
            return FALSE;
        }
    }

    /* ��ʸ�ɵ�(ξ��) */
    if (isset($samma_conf["messagetmplbothpath"])) {
        $ret = write_file($samma_conf["messagetmplbothpath"], JP_ENCODE, $templ_data_both);
        if ($ret === FALSE) {
            $err_msg = "��ʸ�ɵ��ƥ�ץ졼�ȥե�����(ξ��)�ν񤭹���˼��Ԥ��ޤ�����($err_msg)";
            return FALSE;
        }
    }

    return TRUE;
}

/***********************************************************
 * �������
 **********************************************************/
/* �ͤν���� */
$err_msg = "";
$templ_data_jp = "";
$templ_data_en = "";
$templ_data_both = "";
$tag = array();
$arr_errmsg = array();

/* POST���ͤ����� */
if (isset($_POST["templ_data_jp"]) === TRUE) {
    $templ_data_jp = $_POST["templ_data_jp"];
}

if (isset($_POST["templ_data_jp"]) === TRUE) {
    $templ_data_en = $_POST["templ_data_en"];
}

if (isset($_POST["templ_data_jp"]) === TRUE) {
    $templ_data_both = $_POST["templ_data_both"];
}

/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ����ե����롦���ִ����ե������ɹ������å��������å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* SaMMA����ե������ɹ��� */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaconf"]);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* �ե�����ѥ���̤����ξ�� */
/* ��ʸ�ɵ�(���ܸ�) */
if (!isset($samma_conf["messagetmpljppath"]) ||
        ($samma_conf["messagetmpljppath"] === "")) {
    array_push($arr_errmsg, "����ե������Խ����̤���ʸ�ɵ��Υե�����ѥ�(���ܸ�)�����ꤷ�Ƥ���������");
}

/* ��ʸ�ɵ�(�Ѹ�) */
if (!isset($samma_conf["messagetmplenpath"]) ||
        ($samma_conf["messagetmplenpath"] === "")) {
    array_push($arr_errmsg, "����ե������Խ����̤���ʸ�ɵ��Υե�����ѥ�(�Ѹ�)�����ꤷ�Ƥ���������");
}

/* ��ʸ�ɵ�(ξ��) */
if (!isset($samma_conf["messagetmplbothpath"]) ||
    ($samma_conf["messagetmplbothpath"] === "")) {
    array_push($arr_errmsg, "����ե������Խ����̤���ʸ�ɵ��Υե�����ѥ�(ξ��)�����ꤷ�Ƥ���������");
}

/* �ե�����ѥ���̤����ξ�� */
if (count($arr_errmsg) >= 1) {
    $err_msg = implode("<br>", $arr_errmsg);
    $log_msg = implode(", ", $arr_errmsg);
    result_log(OPERATION . ":NG:" . $log_msg);

    /* ���̤Υ������� */
    set_tag_common($tag);

    set_tag_data($tag, "", "", "");

    /* �ڡ����ν��� */
    $ret = display(TMPLFILE, $tag, array(), "", "");
    if ($ret === FALSE) {
        result_log($log_msg, LOG_ERR);
        syserr_display();
        exit(1);
    }

    exit(0);
}

/* commandpass���� */
if (isset($samma_conf["commandpass"]) === TRUE) {
    $commandpass = $samma_conf["commandpass"];
}

/***********************************************************
 * main����
 **********************************************************/

/* �ѹ��ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["mod"]) === TRUE) {

    /* ��ʸ�ɵ�(���ܸ�) */
    $ret = check_input_data($templ_data_jp, $templ_data_en, $templ_data_both);

    /* ���ϥ����å� */
    if (!$ret) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        
        /* �ƥ�ץ졼�ȥե�����ν񤭴��� */
        $ret = write_data_to_file($templ_data_jp, $templ_data_en, $templ_data_both);

        if ($ret === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        } else {
            /* samma�ƥ�ץ졼�ȤΥ���� */
            $ret = reload_samma(ADDMSG);
            if ($ret === FALSE) {
                result_log(OPERATION . ":NG:" . $err_msg);
            } else {
                /* ���ｪλ���� */
                $err_msg = "�귿ʸ�ե�����򹹿����ޤ�����";
                result_log(OPERATION . ":OK:" . $err_msg);

                /* SaMMA������˥塼���̤� */
                dgp_location("../index.php", $err_msg);
                exit(0);
            }
        }
    }

/* ����󥻥�ܥ��󤬲����줿�Ȥ� */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* SaMMA������˥塼���̤� */
    dgp_location("../index.php", $err_msg);
    exit(0);
}

/* ���ɽ�� */
if (isset($_POST["mod"]) === FALSE) {

    /* �ƥ�ץ졼�ȥե�������ɤ߹��� */
    $ret = read_addmsg_tmpl($templ_data_jp, $templ_data_en, $templ_data_both);
    if (!$ret) {
        result_log(OPERATION . ":NG:" . $err_msg);
        syserr_display();
        exit (1);
    }
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ���̤Υ������� */
set_tag_common($tag);

set_tag_data($tag, $templ_data_jp, $templ_data_en, $templ_data_both);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
