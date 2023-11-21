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
 * $RCSfile: samma_list.php,v $
 * $Revision: 1.7 $
 * $Date: 2013/08/30 06:04:58 $
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

define("OPERATION", "�桼���μ������������");
define("TMPLFILE", "samma/samma_user_rcpt_menu.tmpl");
define("STATUS_INPUT",  0);
define("STATUS_SEARCH", 1);

/*********************************************************
 * hidden_result
 *
 * hidden���Ϥ��ǡ������֤�����
 *
 * [����]
 *       $tag���֤���������
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function hidden_result(&$tag) {
    global $sesskey;
    global $user;
    global $dispusr;
    global $del_list;
    global $mode;
    global $ldapdata;
    global $userdn;
    global $form_name;

    /* DN�ΰŹ沽 */
    $userdn = base64_encode($userdn);
    $userdn = str_rot13($userdn);

    /* �����桼��̾�ΰŹ沽 */
    $form_name = base64_encode($form_name);
    $form_name = str_rot13($form_name);

    /* hidden���Ϥ��ǡ������Ǽ */
    $hiddendata['dn'] = $userdn;
    $hiddendata['sk'] = $sesskey;
    $hiddendata['page'] = $_POST["page"];
    $hiddendata['filter'] = $_POST["filter"];
    $hiddendata['form_name'] = $form_name;
    $hiddendata['name_match'] = $_POST['name_match'];

    /* �ݻ����������� */
    $hidden_del = "";
    if (is_array($del_list) === TRUE) {
        foreach ($del_list as $delval) {
            $hidden_del .= "<input type=\"hidden\" name=\"delete[]\" value=\"$delval\">";
        }
    }

    $hidden = "";
    foreach($hiddendata as $hidkey => $hidval) {
        $hidval = escape_html($hidval);
        $hidden .= "<input type=\"hidden\" name=\"$hidkey\" value=\"$hidval\">";
    }

    $tag["<<HIDDEN>>"] = $hidden;
    $tag["<<HIDDEN_DEL>>"] = $hidden_del;

    return TRUE;

}

/*********************************************************
 * print_result
 *
 * �������ɽ������
 *
 * [����]
 *       $looptag
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function print_result(&$looptag)
{
    global $rp_data;
    global $del_list;

    $domain_count = 0;

    /* �ǡ����ʤ��϶�ɽ�� */
    if (is_array($rp_data) === FALSE) {
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

        /* �ѥ���� */
        if ($value != "") {
            $passwd = DISP_INDIVI;
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
        $cnv_key = str_replace("'", "\'", $key);

        $looptag[$domain_count]["<<KEY>>"] = $key;
        $looptag[$domain_count]["<<DOMAIN>>"] = $domain;
        $looptag[$domain_count]["<<PASSWD>>"] = $passwd;
        $looptag[$domain_count]["<<RULE>>"] = $rule;
        $looptag[$domain_count]["<<MOD_BTN>>"] = "<input type=\"button\" class=\"list_mod_btn\" onClick=\"allSubmit('samma_mod.php', '$cnv_key')\" title=\"�Խ�\">";

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

/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* �桼�������Ǽ */
if (isset($_POST["dn"]) === TRUE) {
    $dn = $_POST["dn"];
    $userdn = str_rot13($dn);
    $userdn = base64_decode($userdn);
}
if (isset($_POST["page"]) === TRUE) {
    $page = $_POST["page"];
}
if (isset($_POST["filter"]) === TRUE) {
    $filter = $_POST["filter"];
}

/* ����ե����롢���ִ����ե������ɹ������å��������å� */
$ret = init();
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
/* �ڡ����η��������å� */
if (is_num_check($page) === FALSE) {
    $err_msg = "�ڡ������ͤ������Ǥ���";
    syserr_display();
    exit (1);
}

/* �ե��륿��ʣ�粽 */
if (sess_key_decode($filter, $dec_filter) === FALSE) {
    syserr_display();
    exit (1);
}

/* �ե��륿�η��������å� */
$fdata = explode(':', $dec_filter);
if (count($fdata) != 3) {
    $err_msg = "�ե��륿�η����������Ǥ���";
    syserr_display();
    exit (1);
}

/* DN�η��������å� */
$len = (-1) * strlen($web_conf[$url_data['script']]['ldapbasedn']);
$cmpdn = substr($userdn, $len);
if (strcmp($cmpdn, $web_conf[$url_data['script']]['ldapbasedn']) != 0) {
    $err_msg = "DN�η����������Ǥ���";
    syserr_display();
    exit (1);
}

/* �桼������μ��� */
$ret = get_userdata($userdn);
if ($ret !== TRUE) {
    if ($ret !== LDAP_ERR_BIND) {
        $err_msg = "���ꤵ�줿�桼���Ϥ��Ǥ˺������Ƥ��ޤ���";
    }
    result_log(OPERATION . ":NG:" . $err_msg);
    syserr_display();
    exit (1);
}

$user = $ldapdata[0]["uid"][0];

$dispusr = $web_conf[$url_data['script']]['displayuser'];
$dispusr = escape_html($ldapdata[0][$dispusr][0]);

/* �ե���������Ǽ */
$form_name = $_POST["form_name"];
$name_match = $_POST["name_match"];

/* �ݻ����ͼ��� */
$del_list = "";
if (isset($_POST["delete"]) === TRUE) {
    $del_list = $_POST["delete"];
}
/* ������ʬ�� */
/* �����ɲ� */
if (isset($_POST["new_add"]) === TRUE) {
    /* �ɲò��̤����� */
    page_location("samma_add.php", $del_list);
    exit (0);

/* �����å�������Τ��� */
} elseif (isset($_POST["check_del"]) === TRUE) {
    /* �����å��ʤ� */
    if (isset($_POST["delete"]) === FALSE) {
        $err_msg = "����оݤ����򤵤�Ƥ��ޤ���";
    } else {
        /* �ɥᥤ��/�᡼�륢�ɥ쥹�κ�� */
        $ret = ldap_enc_del($userdn, $_POST["delete"]);
        /* ����оݤ����ʤ����顼���̾泌�顼 */
        if ($ret === LDAP_ERR_NODATA) {
            $err_msg = "�����������" . $err_msg;
            result_log(OPERATION . ":NG:" . $err_msg);
            /* ���������ä���� */
            if ($suc_msg != "") {
                $suc_msg = "�����������" . $suc_msg;
                result_log(OPERATION . ":OK:" . $suc_msg);
                $err_msg .= "<br>" . $suc_msg;
                $del_list = array();
            }
        /* ���顼�ϥ����ƥ२�顼 */
        } elseif ($ret!== LDAP_OK) {
            result_log(OPERATION . ":NG:" . $err_msg);
            syserr_display(CONTENT);
            exit (1);
        /* ���� */
        } else {
            $err_msg = "�����������" . $suc_msg;
            result_log(OPERATION . ":OK:" . $err_msg);
            $del_list = array();
        }

    }

/* ����󥻥� */
} elseif (isset($_POST["cancel"]) === TRUE) {

    /* �������̤����� */
    dgp_location_search("index.php", $err_msg);
    exit (0);
}

/* ������������� */
$rp_data = [];
$ret = get_user_data($userdn, $rp_data);
if ($ret === FALSE) {
    result_log(OPERATION . ":OK:" . $err_msg);
    syserr_display();
    exit (1);
}

/***********************************************************
 * ɽ������
 **********************************************************/
/* ���̤Υ������� */
set_tag_common($tag);

/* hidden�Υ������� */
hidden_result($tag);

/* ɽ����̤Υ������� */
print_result($looptag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, $looptag, STARTTAG, ENDTAG);
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
