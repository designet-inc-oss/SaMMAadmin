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
 * �����ɥᥤ��������Ͽ����
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
�ƥڡ����������
*********************************************************/

define("OPERATION", "�����ɥᥤ�������ɲ�");
define("TMPLFILE", "samma/samma_admin_sender_add.tmpl");

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
function set_tag_data(&$tag) {
    global $sesskey;
    global $sender_name;
    global $status;

    /* �ɥᥤ��̾ */
    if (isset($sender_name) === TRUE) {
        $domain = escape_html($sender_name);
    }
    $tag["<<DOMAIN>>"] = $domain;

    /* �Ź沽�롼�� */
    $rule1 = "checked";
    $rule0 = "";

    /* ���ݻ��Ź�Ƚ�� */
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
 * ��Ͽ�ǡ����Υ����å�
 *
 * [����]
 *      $add_data       �ɲåǡ���
 *      $status         �饸���ܥ�����
 *
 * [�֤���]
 *      TRUE            ����
 *      FALSE           �۾�
 **********************************************************/
function check_senderadd_dbdata($add_data, $status)
{
    global $err_msg;

    /* �����Ͷ������å�*/
    if ($add_data == "") {
        $err_msg = "�ɥᥤ��̾/�᡼�륢�ɥ쥹�����Ϥ���Ƥ��ޤ���";
        return FALSE;
    }

    /* �ɥᥤ��/�᡼�륢�ɥ쥹���������å� */
    $ret = check_samma_mail($add_data);
    if ($ret === FALSE) {
        $err_msg = "�ɥᥤ��̾/�᡼�륢�ɥ쥹��" . $err_msg;
        return FALSE;
    }

    /* �Ź沽�롼����������å� */
    if (check_flg($status) === FALSE) {
        $err_msg = "�ѥ���ɤη����������Ǥ���";
        return FALSE;
    }

   return TRUE;
}

/*********************************************************
 * add_sender_dbdata
 *
 * �ǡ������ɲ�
 *
 * [����]
 *      $add_data       �ɲåǡ���
 *      $status         �饸���ܥ�����
 *
 * [�֤���]
 *      SUCCESS         ����
 *      FAIL            �۾�
 *      FAIL_EXIST      �۾�(���˥ǡ�������)
 **********************************************************/
function add_sender_dbdata($add_data, $status)
{
    global $db_file;
    global $db_type;

    /* ��Ͽ���ɥ쥹�Ѵ� */
    if ($status == 0) {
        $domain = "!" . $add_data;
        $check_domain = $add_data;
    } else {
        $domain = $add_data;
        $check_domain = "!" . $add_data;
    }

    /* �ɥᥤ��/�᡼�륢�ɥ쥹����Ͽ */
    $ret = db_add($db_file, $db_type, $domain, $check_domain, "");
    if ($ret !== SUCCESS) {
        return $ret;
    }

    return SUCCESS;
}

/***********************************************************
 * �������
 **********************************************************/
/* �ѿ��ν���� */
$err_msg = "";
$sender_name = "";
$status = 1;
$del_list = "";
$tag = array();

/* POST���ϤäƤ����ͤ����� */
if (isset($_POST["sender_name"]) === TRUE) {
    $sender_name = strtolower($_POST["sender_name"]);
}
if (isset($_POST["status"]) === TRUE) {
    $status = $_POST["status"];
}
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
$db_type = "";
if (isset($samma_conf["senderdb"]) === TRUE) {
    $files = explode(":", $samma_conf["senderdb"], 2);
    $db_type = $files[0];
    $db_file = $files[1];
} else {
    $err_msg = "DB�ե����뤬���ꤵ��Ƥ��ޤ���";
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* ��Ͽ�ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["add"]) === TRUE) {

    /* ���ϥǡ������ͥ����å� */
    $ret = check_senderadd_dbdata($sender_name, $status);
    if ($ret === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* �ɥᥤ��/�᡼�륢�ɥ쥹����Ͽ */
        $ret = add_sender_dbdata($sender_name, $status);
        if ($ret === FAIL_EXIST) {
            $err_msg = "�����ɥᥤ�������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
        } elseif ($ret !== SUCCESS) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display();
            exit (1);
        } else {
            /* ���ｪλ���� */
            $err_msg =  "�����ɥᥤ���������Ͽ���ޤ�����(" . $sender_name . ")";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* �����ɥᥤ��������̤� */
            dgp_location("index.php", $err_msg);
            exit (0);
        }
    }

/* ����󥻥�ܥ��󤬲����줿�Ȥ� */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* �����ɥᥤ��������̤� */
    dgp_location("index.php");
    exit (0);
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ���̤Υ������� */
set_tag_common($tag);

/* body��ɽ������ */
set_tag_data($tag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
