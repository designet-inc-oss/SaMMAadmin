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
 * �桼����SaMMA�����԰�������
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.9 $
 * $Date: 2013/08/22 09:32:08 $
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

define("TMPLFILE", "samma/samma_user_menu.tmpl");
define("OPERATION", "�������������");

/***********************************************************
 * �ؿ�
 **********************************************************/
/*********************************************************
 * print_result
 *
 * �������ɽ������
 *
 * [����]
 *      $tag  �֤��������������� 
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function print_result(&$looptag)
{
    global $rp_data;
    global $del_list;

    /* �ɥᥤ��� */
    $domain_count = 0;

    /* �ǡ����ʤ��϶�ɽ�� */
    if (is_array($rp_data) === FALSE) {
        $looptag = array();
        return;
    }

    /* ������ */
    uksort($rp_data, DOMAIN_SORT);

    /* ɽ�� */
    foreach ($rp_data as $key => $value) {
        /* ������� */
        $rule = DISP_EFFECT;
        $passwd = DISP_RANDOM;
        $domain = $key;
        $rawpw = "";

        /* �ѥ���� */
        if ($value != "") {
            $passwd = DISP_INDIVI;
            $rawpw = $value;
        }

        /* �Ź沽�롼����� */
        $str = explode("!", $key, 2);
        if ($str[0] != $key) {
            $domain = $str[1];
            $rule = DISP_INEFFECT;
            $passwd = DISP_NOPASS;
        }

        /* ���������� */
        $domain = escape_html($domain);
        $rawpw = escape_html($rawpw);
        $cnv_key = str_replace("'", "\'", $key);

        $looptag[$domain_count]["<<KEY>>"] = $key;
        $looptag[$domain_count]["<<DOMAIN>>"] = $domain;
        $looptag[$domain_count]["<<PASSWD>>"] = $passwd;
        $looptag[$domain_count]["<<RAWPD>>"] = $rawpw;
        $looptag[$domain_count]["<<RULE>>"] = $rule;
        $looptag[$domain_count]["<<MOD_BTN>>"] = <<<EOD
<input type="button" class="list_mod_btn" onClick="allSubmit('samma_mod.php', '$cnv_key')" title="�Խ�">

EOD;
        $domain_count++;
    }
    return TRUE;

}

/***********************************************************
 * �������
 **********************************************************/
$tag = array();
$looptag = array();

/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = user_init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* ��å��������� */
if (isset($_POST["msg"]) === TRUE) {
    $err_msg = escape_html($_POST["msg"]);
}

/***********************************************************
 * main����
 **********************************************************/
/* �桼��̾��Ǽ */
$user = $env['loginuser'];
$userdn = $env['user_selfdn'];

/* �桼������μ��� */
$ret = get_userdata($userdn);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "�桼������μ����˼��Ԥ��ޤ�����";
    syserr_display();
    exit (1);
}

$dispusr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = escape_html($ldapdata[0][$dispusr][0]);

/* �ݻ����ͼ��� */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}

/* ������ʬ�� */
/* �����ɲ� */
if (isset($_POST["new_add"]) === TRUE) {
    /* �ɲò��̤����� */
    dgp_location("samma_add.php");
    exit (0);

/* �����å�������Τ��� */
} elseif (isset($_POST["check_del"]) === TRUE) {
    /* �����å��ʤ� */
    if (isset($_POST["delete"]) === FALSE) {
        $err_msg = "����оݤ����򤵤�Ƥ��ޤ���";
    } else {
        /* �桼���ǤΥХ���ɤ�̵���� */
        $env['user_self'] = FALSE;

        /* �ɥᥤ��/�᡼�륢�ɥ쥹�κ�� */
        $ret = ldap_enc_del($userdn, $_POST["delete"]);
        /* ����оݤ����ʤ����顼���̾泌�顼 */
        if ($ret === LDAP_ERR_NODATA) {
            $err_msg = "�����������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ��������ä���� */
            if ($suc_msg != "") {
                $suc_msg = "�����������" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
            }
        /* ���顼�ϥ����ƥ२�顼 */
        } elseif ($ret !== LDAP_OK) {
            result_log(OPERATION . ":NG:" . $err_msg);
            /* �桼���ѥ�å��������ѹ� */
            $delete = implode(", ", $_POST["delete"]);
            $err_msg = "����������κ���˼��Ԥ��ޤ�����(" . $delete . ")";
            syserr_display();
            exit (1);
        /* ���� */
        } else {
            $err_msg = "�����������" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
        }

    }
}

/* ������������� */
$rp_data = array();
/* �桼���ǤΥХ���ɤ�̵���� */
$env['user_self'] = FALSE;
$ret = get_user_data($userdn, $rp_data);
if ($ret === FALSE) {
    result_log(OPERATION . ":NG:" . $err_msg);
    $err_msg = "�������������μ����˼��Ԥ��ޤ�����";
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
