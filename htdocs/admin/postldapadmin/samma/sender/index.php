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
 * �����ɥᥤ�������������
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
�ƥڡ����������
*********************************************************/

define("TMPLFILE", "samma/samma_admin_sender_menu.tmpl");
define("OPERATION",  "�����ɥᥤ���������");

/*********************************************************
 * print_result
 *
 * �������ɽ������
 *
 * [����]
 *       �ʤ�
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function print_result(&$looptag)
{
    global $db_data;
    global $del_list;
    global $sesskey;

    /* �ɥᥤ��� */
    $domain_count = 0;

    /* �ǡ�����̵���Ȥ��϶�ɽ�� */
    if (is_array($db_data) === FALSE) {
        $looptag = array();
        return;
    }

    /* �����ǥ����Ȥ���(����) */
    uksort($db_data, DOMAIN_SORT);

    /* �ɥᥤ��̾/�᡼�륢�ɥ쥹ɽ������ */
    foreach($db_data as $key => $value) {

        /* ������� */
        $rule = DISP_EFFECT;
        $domain = $key;
        $checked = "";

        /* �Ź沽�롼����� */
        $str = explode("!", $key, 2);
        if ($str[0] != $key) {
            $domain = $str[1];
            $rule = DISP_INEFFECT;
        }

        /* ���������� */
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
 * �������
 **********************************************************/

/* �ͤν���� */
$tag = array();
$looptag = array();
$err_msg = "";
$del_list = "";

/* POST���ϤäƤ����ͤ����� */
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
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

/***********************************************************
 * main����
 **********************************************************/

/* DB�ե�����ѥ����� */
$db_file = "";
if (isset($samma_conf["senderdb"]) === TRUE) {
    $files = explode(":", $samma_conf["senderdb"], 2);
    $db_file = $files[1];
} else {
    $err_msg = "DB�ե����뤬���ꤵ��Ƥ��ޤ���";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* ��Ͽ�ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["new_add"]) === TRUE) {

    /* �����ɥᥤ�������ɲò��̤� */
    dgp_location("add.php");
    exit (0);

/* ����ܥ��󤬲����줿�Ȥ� */
} elseif (isset($_POST["del"]) === TRUE) {

    /* ����������ϥ����å�*/
    if (is_array($del_list) === FALSE) {
        $err_msg = "����оݤ����򤵤�Ƥ��ޤ���";
    } else { 
        /* �ɥᥤ��/�᡼�륢�ɥ쥹�κ�� */
        $ret = db_del($db_file, $del_list);
        if ($ret === FAIL) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } elseif ($ret === FAIL_DEL) {
            $err_msg = "�����ɥᥤ�������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            if ($suc_msg != "") {
                $suc_msg = "�����ɥᥤ�������" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
            }
        } else {
            /* ���ｪλ���� */
            $err_msg = "�����ɥᥤ�������" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
        }
    }
}

/* �ǡ����١������� */
$ret = db_search($db_file, $db_data);
if ($ret === FAIL) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ���̤Υ������� */
set_tag_common($tag);

/* ��̤�������������֤����� */
print_result($looptag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, $looptag, STARTTAG, ENDTAG);
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
