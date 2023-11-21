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
 * ����饤�󥹥ȥ졼���Υƥ�ץ졼���Խ�����
 *
 * $RCSfile: index.php,v $
 * $Revision: 5.00 $
 * $Date: 2021/05/27 10:10:10 $
 **********************************************************/
define("OPERATION",      "os_uploader�����Υե�����ƥ�ץ졼���Խ�");
define("TEMPLATEPATH",   "template_file");

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
define("TMPLFILE", "samma/samma_admin_osuploadertmpl.tmpl");

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
function set_tag_data(&$tag) {

    global $sesskey;
    global $templ_data;

    /* html���������� */
    $cnv_templ_data = escape_html($templ_data);
    $tag["<<TMPL_DATA>>"] = $cnv_templ_data;

    return TRUE;

}

/***********************************************************
 * �������
 **********************************************************/
/* �ͤν���� */
$err_msg = "";
$templ_data = "";
$tag = array();

/* POST���ͤ����� */
if (isset($_POST["templ_data"]) === TRUE) {
    $templ_data = $_POST["templ_data"];
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

/* ����饤�󥹥ȥ졼��������ե�������ɤ߹��� */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaosuploaderconf"]);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

/* ����饤�󥹥ȥ졼��������ե������TEMPLATE_FILE��̤���� */
if (!isset($samma_conf[TEMPLATEPATH])) {
    $err_msg = "����饤�󥹥ȥ졼��Ϣ��������̤ǥƥ�ץ졼�ȥե�����ѥ������ꤷ�Ƥ���������";
    result_log(OPERATION . ":NG:" . $err_msg);

    /* ���̤Υ������� */
    set_tag_common($tag);

    set_tag_data($tag);

    /* �ڡ����ν��� */
    $ret = display(TMPLFILE, $tag, array(), "", "");
    if ($ret === FALSE) {
        result_log($log_msg, LOG_ERR);
        syserr_display();
        exit(1);
    }

    exit(0);
}

/***********************************************************
 * main����
 **********************************************************/

/* �ѹ��ܥ��󤬲����줿�Ȥ� */
if (isset($_POST["mod"]) === TRUE) {

    /* ���ϥ����å� */
    if (empty($templ_data) === TRUE) {
        $err_msg = "�ƥ����ȥե�����ƥ�ץ졼�Ȥ����Ϥ���Ƥ��ޤ���";
        result_log(OPERATION . ":OK:" . $err_msg);
    } else {
        /* �ƥ�ץ졼�ȥե�����ν񤭴��� */
        $ret = write_file($samma_conf[TEMPLATEPATH], STRCODE, $templ_data);
        if ($ret === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        } else {
            /* ���ｪλ���� */
            $err_msg = "����饤�󥹥ȥ졼���Υƥ�ץ졼�Ȥ򹹿����ޤ�����";
            result_log(OPERATION . ":OK:" . $err_msg);

            /* SaMMA������˥塼���̤� */
            dgp_location("../index.php", $err_msg);
            exit(0);
        }
    }

/* ����󥻥�ܥ��󤬲����줿�Ȥ� */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* SaMMA������˥塼���̤� */
    dgp_location("../index.php", $err_msg);
    exit(0);
}

/* ���ɽ�� */
if (isset($_POST["templ_data"]) === FALSE) {

    /* �ƥ�ץ졼�ȥե�������ɤ߹��� */
    $ret = read_file($samma_conf[TEMPLATEPATH], $templ_data);
    if ($ret === FALSE) {
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

set_tag_data($tag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
