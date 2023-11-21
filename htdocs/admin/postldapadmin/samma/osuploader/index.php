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
 * ����饤�󥹥ȥ졼��Ϣ���������
 *
 * $RCSfile: index.php,v $
 * $Revision: 5.00 $
 * $Date: 2021/05/26 10:10:10 $
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

define("OPERATION", "����饤�󥹥ȥ졼��Ϣ������");
define("TMPLFILE", "samma/samma_admin_osuploader.tmpl");

/*********************************************************
 * check_tmpl
 *
 * �ƥ�ץ졼�ȥե���������å�
 *
 * [����]
 *       $filename      �ƥ�ץ졼�ȥե�����
 *
 * [�֤���]
 *      TRUE            ����
 *      FALSE           �۾�
 **********************************************************/
function check_tmpl($filename)
{
    global $err_msg;

    /* ���������å���*/
    $ret = check_file($filename);
    if ($ret === FAIL) {
        $err_msg = "�ƥ�ץ졼��" . $err_msg;
        return FALSE;
    /* �ե����뤬�ʤ����Ϻ��� */
    } elseif ($ret === FAIL_NO_EXIST) {
        /* �ǥ��쥯�ȥ�񤭹��߸������å� */
        if (is_writable(dirname($filename)) !== TRUE) {
            $err_msg = "�ǥ��쥯�ȥ�˽���߸�������ޤ���(" . $filename . ")";
            return FALSE;
        }
    }

    return TRUE;
}

/***********************************************************
 * display_result()
 *
 * �֤����������ξ���򥻥åȤ���
 *
 * [����]
 *      $tag  �֤���������������
 *
 * [�֤���]
 *       �ʤ�
 **********************************************************/
function display_result(&$tag) 
{
    global $sesskey;
    global $samma_conf;
    global $logfacility;
    global $web_conf; 

    /* NextCloud��API�Υȥåפ�URL */
    $nc_url = "";
    if (isset($samma_conf["nc_url"]) === TRUE && 
              $samma_conf["nc_url"] != "") {
        $nc_url = escape_html($samma_conf["nc_url"]);
    }
    $tag["<<NC_URL>>"] = $nc_url;

    /* NextCloud�δ����ԥ桼��ID */
    $nc_admin = "";
    if (isset($samma_conf["nc_admin"]) === TRUE && 
              $samma_conf["nc_admin"] != "") {
        $nc_admin = escape_html($samma_conf["nc_admin"]);
    }
    $tag["<<NC_ADMIN>>"] = $nc_admin;

    /* NextCloud�δ����ԥѥ���� */
    $nc_adminpw = "";
    if (isset($samma_conf["nc_adminpw"]) === TRUE && 
              $samma_conf["nc_adminpw"] != "nc_adminpw") {
        $nc_adminpw = escape_html($samma_conf["nc_adminpw"]);
    }
    $tag["<<NC_ADMINPW>>"] = $nc_adminpw;

    /* NextCloud�ؤΥ������������ॢ���� */
    $nc_timeout = "";
    if (isset($samma_conf["nc_timeout"]) === TRUE && 
              $samma_conf["nc_timeout"] != "") {
        $nc_timeout = escape_html($samma_conf["nc_timeout"]);
    }
    $tag["<<NC_TIMEOUT>>"] = $nc_timeout;

    /* HTTPS������ */
    $https_cert = "";
    if (isset($samma_conf["https_cert"]) === TRUE && 
              $samma_conf["https_cert"] != "") {
        $https_cert = escape_html($samma_conf["https_cert"]);
    }
    $tag["<<HTTPS_CERT>>"] = $https_cert;

    /* �ƥ�ץ졼�ȥե�����̾ */
    $template_file = "";
    if (isset($samma_conf["template_file"]) === TRUE && 
              $samma_conf["template_file"] != "") {
        $template_file = escape_html($samma_conf["template_file"]);
    }
    $tag["<<TEMPLATE_FILE>>"] = $template_file;

    /* StrCode���� */
    $str_code = "";
    if (isset($samma_conf["str_code"]) === TRUE && 
              $samma_conf["str_code"] != "") {
        $str_code = escape_html($samma_conf["str_code"]);
    }
    $tag["<<STR_CODE>>"] = $str_code;

    /* 1���å����������NextCloud�˥��åץ��ɥե������ */
    $concurrent = "";
    if (isset($samma_conf["concurrent"]) === TRUE &&
              $samma_conf["concurrent"] != "") {
        $concurrent = escape_html($samma_conf["concurrent"]);
    }
    $tag["<<CONCURRENT>>"] = $concurrent;

    /* NextCloud�������Τ��줿��ͭURL�Υץ�ȥ������Ū��https���ѹ�����ե饰 */
    $flag_on = "";
    $flag_off = "checked";
    if (isset($samma_conf["force_https"]) === TRUE && 
              $samma_conf["force_https"] != "") {
        if (strtolower($samma_conf["force_https"]) === "true") {
            $flag_on = "checked";
            $flag_off = "";
        }
    }
    $tag["<<FORCE_HTTPS_ON>>"] = $flag_on;
    $tag["<<FORCE_HTTPS_OFF>>"] = $flag_off;

    return TRUE;
}

/*********************************************************
 * check_conf_data
 *
 * ����ե�����ǡ��������å�
 *
 * [����]
 *       $data		����ե�����ǡ���
 *
 * [�֤���]
 *	TRUE		����
 *	FALSE		�۾�
 **********************************************************/
function check_conf_data(&$data)
{
    global $err_msg;
    global $logfacility;
    global $str_code;
    global $samma_conf;

    /*
     * ɬ�ܹ��ܤΥ����å�
     */
    /* NextCloud��API�Υȥåפ�URL */
    if ($data["nc_url"] === "") {
        $err_msg = "NextCloud��API�Υȥåפ�URL�����Ϥ���Ƥ��ޤ���";
        return FALSE;
    }

    /* NextCloud�δ����ԥ桼��ID */
    if ($data["nc_admin"] === "") {
        $err_msg = "NextCloud�δ����ԥ桼��ID�����Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* NextCloud�δ����ԥѥ���� */
    if ($data["nc_adminpw"] === "") {
        $err_msg = "NextCloud�δ����ԥѥ���ɤ����Ϥ���Ƥ��ޤ���";
        return FALSE;
    }

    /*
     * ���������å�
     */

    /* NextCloud�ؤΥ������������ॢ���� */
    if ($data["nc_timeout"] !== "") {
        if (check_integer($data["nc_timeout"]) === FALSE) {
            $err_msg = "NextCloud�ؤΥ������������ॢ���Ȥ�1�ʾ�����������ꤷ�Ƥ���������";
            return FALSE;
        }
    }

    /* �ƥ�ץ졼�ȥե�����̾ */
    if ($data["template_file"] !== "") {
        /* ���������å�(�ʤ���к���) */
        if (check_tmpl($data["template_file"]) === FALSE) {
            return FALSE;
        }
    }

    /* 1���å����������NextCloud�˥��åץ��ɥե������ */
    if ($data["concurrent"] !== "") {
        if (check_integer($data["concurrent"]) === FALSE) {
            $err_msg = "1���å����������NextCloud�˥��åץ��ɥե��������1�ʾ�����������ꤷ�Ƥ���������";
            return FALSE;
        }
    }

    return TRUE;
}

/*********************************************************
 * check_messagetmplpath
 *
 * ��ʸ�ɵ��Υե�����ѥ��������ͤ�����å�����
 * �ե������¸�ߤ��ʤ���硢���ե�������������
 * 
 * [����]
 *       $jp_path       ��ʸ�ɵ��Υե�����ѥ�(���ܸ�)
 *       $en_path       ��ʸ�ɵ��Υե�����ѥ�(�Ѹ�)
 *       $both_path     ��ʸ�ɵ��Υե�����ѥ�(ξ��)
 *       &$errormsg     ���顼��å�����(���ȥǡ���)
 *
 * [�֤���]
 *      TRUE            ����
 *      FALSE           �۾�
 **********************************************************/
function check_messagetmplpath($jp_path, $en_path, $both_path, &$errormsg)
{
    if (strlen($jp_path) > 256) {
        $errormsg = "��ʸ�ɵ��Υե�����ѥ�(���ܸ�)��Ĺ�����Ǥ���($jp_path)";
        return FALSE;
    }

    if (strlen($en_path) > 256) {
        $errormsg = "��ʸ�ɵ��Υե�����ѥ�(�Ѹ�)��Ĺ�����Ǥ���($en_path)";
        return FALSE;
    }

    if (strlen($both_path) > 256) {
        $errormsg = "��ʸ�ɵ��Υե�����ѥ�(ξ��)��Ĺ�����Ǥ���($both_path)";
        return FALSE;
    }

    if ($jp_path !== "") {
        /* �ե������¸�ߤ�����å� */
        if (!file_exists($jp_path)) {
            /* �ե��������� */
            if (touch($jp_path) === FALSE) { 
                $errormsg = "��ʸ�ɵ��Υե�����(���ܸ�)�κ����˼��Ԥ��ޤ�����($jp_path)";
                return FALSE;
            }
        }
       /* �̾�ե����뤫�ɤ�����Ĵ�٤� */
       if(!is_file($jp_path)) {
            $errormsg = "��ʸ�ɵ��Υե�����(���ܸ�)���̾�ե�����ǤϤ���ޤ���($jp_path)";
            return FALSE;
       }
    }

    if ($en_path !== "") {
        /* �ե������¸�ߤ�����å� */
        if (!file_exists($en_path)) {
            /* �ե��������� */
            if (!touch($en_path)) {
                $errormsg = "��ʸ�ɵ��Υե�����(�Ѹ�)�κ����˼��Ԥ��ޤ�����($en_path)";
                return FALSE;
            }
        }
       /* �̾�ե����뤫�ɤ�����Ĵ�٤� */
       if(!is_file($en_path)) {
            $errormsg = "��ʸ�ɵ��Υե�����(���ܸ�)���̾�ե�����ǤϤ���ޤ���($en_path)";
            return FALSE;
       }
    }

    if ($both_path) {
        /* �ե������¸�ߤ�����å� */
        if (!file_exists($both_path)) {
            /* �ե��������� */
            if (!touch($both_path)) {
                $errormsg = "��ʸ�ɵ��Υե�����(ξ��)�κ����˼��Ԥ��ޤ�����($both_path)";
                return FALSE;
            }
        }
        /* �̾�ե����뤫�ɤ�����Ĵ�٤� */
        if(!is_file($both_path)) {
            $errormsg = "��ʸ�ɵ��Υե�����(���ܸ�)���̾�ե�����ǤϤ���ޤ���($both_path)";
            return FALSE;
        }
    }

    return TRUE;
}

/*********************************************************
 * check_db
 *
 * �ǡ����١����ե���������å�
 *
 * [����]
 *       $db_file	DB�ե�����
 *	 $db_type	DB����
 *
 * [�֤���]
 *	TRUE		����
 *	FALSE		�۾�
 **********************************************************/
function check_db($db_file, $db_type)
{
    global $err_msg;

    /* ���������å���*/
    $ret = check_file($db_file);
    if ($ret === FAIL) {
        $err_msg = "DB" . $err_msg;
        return FALSE;
    /* DB�ե����뤬�ʤ����Ϻ��� */
    } elseif ($ret === FAIL_NO_EXIST) {
        /* ��DB���� */
        $type = 0;
        if ($db_type == "btree") {
            $type = 1;
        }
        if (make_db($db_file, $type) === FALSE) {
            $err_msg = "DB�Ѥ�" . $err_msg;
            return FALSE;
        }
    /* �����å�OK�ʾ��Ϸ��������å� */
    } else {
        $command = sprintf(CONFIRM_DB, $db_type, escapeshellcmd($db_file));
        $ret = system($command, $result);

        if ($result != 0) {
            $err_msg = "DB�����������Ǥ���";
            return FALSE;
        }
        if ($ret === FALSE){
            $err_msg = "DB�����γ�ǧ�˼��Ԥ��ޤ�����";
            return FALSE;
        }
    }
    return TRUE;

}

/*********************************************************
 * set_disp_data
 *
 * �ͤ�ɽ��������˥��åȤ��ޤ�
 *
 * [����]
 *       $data		�ǡ���
 *	 $disp_data	�ǡ���
 *
 * [�֤���]
 *	�ʤ�
 **********************************************************/
function set_disp_data($data, &$disp_data)
{

    /* NextCloud��API�Υȥåפ�URL */
    if (isset($data["nc_url"]) === TRUE) {
        $disp_data["nc_url"] = $data["nc_url"];
    }

    /* NextCloud�δ����ԥ桼��ID */
    if (isset($data["nc_admin"]) === TRUE) {
        $disp_data["nc_admin"] = $data["nc_admin"];
    }

    /* NextCloud�δ����ԥѥ���� */
    if (isset($data["nc_adminpw"]) === TRUE) {
        $disp_data["nc_adminpw"] = $data["nc_adminpw"];
    }

    /* NextCloud�ؤΥ������������ॢ���� */
    if (isset($data["nc_timeout"]) === TRUE) {
        $disp_data["nc_timeout"] = $data["nc_timeout"];
    }

    /* HTTPS������ */
    if (isset($data["https_cert"]) === TRUE) {
        $disp_data["https_cert"] = $data["https_cert"];
    }

    /* �ƥ�ץ졼�ȥե�����̾ */
    if (isset($data["template_file"]) === TRUE) {
        $disp_data["template_file"] = $data["template_file"];
    }

    /* StrCode���� */
    if (isset($data["str_code"]) === TRUE) {
        $disp_data["str_code"] = $data["str_code"];
    }

    /* 1���å����������NextCloud�˥��åץ��ɥե������ */
    if (isset($data["concurrent"]) === TRUE) {
        $disp_data["concurrent"] = $data["concurrent"];
    }

    /* NextCloud�������Τ��줿��ͭURL�Υץ�ȥ������Ū��https���ѹ�����ե饰 */
    if (isset($data["force_https"]) === TRUE) {
        if (strtolower($data["force_https"]) === "true") {
            $force_https = "True";
        } else {
            $force_https = "False";
        }

        $disp_data["force_https"] = $force_https;
    }

    return;
}

/***********************************************************
 * �������
 **********************************************************/
/* �ͤν���� */
$tag = array();

/* ���å���󥭡����ѿ������� */
if (isset ($_POST["sk"]) === TRUE) {
    $sesskey = $_POST["sk"];
}

/* ����ե����륿�ִ����ե������ɹ������å����Υ����å� */
$ret = init();
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/* SaMMA����ե������ɹ��� */
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaosuploaderconf"]);
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/
/* ������ʬ�� */

/* ���� */
if (isset($_POST["mod"]) === TRUE) {
    /* ���ݻ��ѥǡ������� */
    set_disp_data($_POST, $samma_conf);
    /* ���ϥ����å� */
    if (check_conf_data($samma_conf) === FALSE) {
        result_log(OPERATION . ":NG:" . $err_msg);
    } else {
        /* ���� */
        if (mod_osuploader_conf($samma_conf) === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        /* ���� */
        } else {
            $err_msg = "����饤�󥹥ȥ졼��������ե�����򹹿����ޤ�����";
            result_log(OPERATION . ":OK:" . $err_msg);
           /* ��˥塼���̤����� */
           dgp_location("../index.php", $err_msg);
           exit (0);
        }
    }

/* ����󥻥� */
} elseif (isset($_POST["cancel"]) === TRUE) {
    /* ��˥塼���̤����� */
    dgp_location("../index.php", $err_msg);
    exit (0);

}

/***********************************************************
 * ɽ������
 **********************************************************/

/* ���̤Υ������� */
set_tag_common($tag);

/* ��̤�������������֤����� */
display_result($tag);

/* �ڡ����ν��� */
$ret = display(TMPLFILE, $tag, array(), "", "");
if ($ret === FALSE) {
    result_log($log_msg, LOG_ERR);
    syserr_display();
    exit(1);
}

?>
