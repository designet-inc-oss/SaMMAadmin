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
 * SaMMA����ե������Խ�����
 *
 * $RCSfile: index.php,v $
 * $Revision: 1.16 $
 * $Date: 2013/09/03 03:11:11 $
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

define("OPERATION", "����ե������Խ�");
define("TMPLFILE", "samma/samma_admin_config.tmpl");

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
function display_result(&$tag) {
    global $sesskey;
    global $samma_conf;
    global $logfacility;
    global $db_types;
    global $str_code;
    global $web_conf; 

    /* �ѥ���� */
    $commandpass = "";
    if (isset($samma_conf["commandpass"]) === TRUE && 
              $samma_conf["commandpass"] != "") {
        $commandpass = escape_html($samma_conf["commandpass"]);
    }
    $tag["<<COMMANDPASS>>"] = $commandpass;

    /* ���ե�����ƥ� */
    $html_log = "";
    foreach ($logfacility as $log) {
        $selected = "";
        /* ���פ��������� */
        if (isset($samma_conf["syslogfacility"]) === TRUE &&
                  $samma_conf["syslogfacility"] != "") {
            if ($log == $samma_conf["syslogfacility"]) {
                $selected = "selected";
            } 
        } else {
            /* ���åȤ���Ƥ��ʤ����ϥǥե���� */
            if ($log == "local0") {
                $selected = "selected";
            }
        }
        $html_log .= "<option value=\"$log\" $selected>$log";
        $tag["<<HTML_LOG>>"] = $html_log;
    }

    /* �����Ǽ�ǥ��쥯�ȥ� */
    $encryptiontmpdir = "";
    if (isset($samma_conf["encryptiontmpdir"]) === TRUE && 
              $samma_conf["encryptiontmpdir"] != "") {
        $encryptiontmpdir = escape_html($samma_conf["encryptiontmpdir"]);
    }
    $tag["<<ENCRYPTIONTMPDIR>>"] = $encryptiontmpdir;

    /* zip���ޥ�ɥѥ� */
    $zipcommand = "";
    if (isset($samma_conf["zipcommand"]) === TRUE && 
              $samma_conf["zipcommand"] != "") {
        $zipcommand = escape_html($samma_conf["zipcommand"]);
    }
    $tag["<<ZIPCOMMAND>>"] = $zipcommand;

    /* zip���ޥ�ɥ��ץ���� */
    $zipcommandopt = "";
    if (isset($samma_conf["zipcommandopt"]) === TRUE && 
              $samma_conf["zipcommandopt"] != "") {
        $zipcommandopt = escape_html($samma_conf["zipcommandopt"]);
    }
    $tag["<<ZIPCOMMANDOPT>>"] = $zipcommandopt;

    /* �����ɥᥤ��DB����/�ѥ� */
   $html_sender = "";
    foreach ($db_types as $s_type) {
        $s_file = "";
        $type = "";
        if (isset($samma_conf["senderdb"]) === TRUE &&
                  $samma_conf["senderdb"] != "") {
            $send = explode(":", $samma_conf["senderdb"], 2);
            $type = $send[0];
            $s_file = escape_html($send[1]);
        }
        $selected = "";
        /* ���פ��������� */
        if ($s_type == $type) {
                $selected = "selected";
        }
        $html_sender .= "<option value=\"$s_type\" $selected>$s_type";
        $tag["<<HTML_SENDER>>"] = $html_sender;
    }
    $tag["<<S_FILE>>"] = $s_file;
    
    /* ������DB����/�ѥ� */
    $html_rcpt = "";
    foreach ($db_types as $r_type) {
        $r_file = "";
        $type = "";
        if (isset($samma_conf["rcptdb"]) === TRUE &&
                  $samma_conf["rcptdb"] != "") {
            $rcpt = explode(":", $samma_conf["rcptdb"], 2);
            $type = $rcpt[0];
            $r_file = escape_html($rcpt[1]);
        }
        $selected = "";
        /* ���פ��������� */
        if ($r_type == $type) {
                $selected = "selected";
        }
        $html_rcpt .= "<option value=\"$r_type\" $selected>$r_type";
        $tag["<<HTML_RCPT>>"] = $html_rcpt;
    }
    $tag["<<R_FILE>>"] = $r_file;
        
    /* ���Υ᡼��ƥ�ץ졼�ȥѥ� */
    $templatepath = "";
    if (isset($samma_conf["templatepath"]) === TRUE && 
              $samma_conf["templatepath"] != "") {
        $templatepath = escape_html($samma_conf["templatepath"]);
    }
    $tag["<<TEMPLATEPATH>>"] = $templatepath;


    /* ���������Υ᡼��ƥ�ץ졼�ȥѥ� */
    $rcpttemplatepath = "";
    if (isset($samma_conf["rcpttemplatepath"]) === TRUE &&
              $samma_conf["rcpttemplatepath"] != "") {
        $rcpttemplatepath = escape_html($samma_conf["rcpttemplatepath"]);
    }
    $tag["<<RCPTTEMPLATEPATH>>"] = $rcpttemplatepath;

    /* sendmail���ޥ�ɥѥ�/���ץ���� */
    $sendmailcommand = "";
    if (isset($samma_conf["sendmailcommand"]) === TRUE && 
              $samma_conf["sendmailcommand"] != "") {
        $sendmailcommand = escape_html($samma_conf["sendmailcommand"]);
    }
    $tag["<<SENDMAILCOMMAND>>"] = $sendmailcommand;

    /* �Ź沽ZIP�ե�����ե����ޥå� */
    $zipfilename = "";
    if (isset($samma_conf["zipfilename"]) === TRUE && 
              $samma_conf["zipfilename"] != "") {
        $zipfilename = escape_html($samma_conf["zipfilename"]);
    }
    $tag["<<ZIPFILENAME>>"] = $zipfilename;

    /* �����¸�ǥ��쥯�ȥ� */
    $mailsavetmpdir = "";
    if (isset($samma_conf["mailsavetmpdir"]) === TRUE && 
              $samma_conf["mailsavetmpdir"] != "") {
        $mailsavetmpdir = escape_html($samma_conf["mailsavetmpdir"]);
    }
    $tag["<<MAILSAVETMPDIR>>"] = $mailsavetmpdir;

    /* �ѥ����ʸ���� */
    $passwordlength = "";
    if (isset($samma_conf["passwordlength"]) === TRUE && 
              $samma_conf["passwordlength"] != "") {
        $passwordlength = escape_html($samma_conf["passwordlength"]);
    }
    $tag["<<PASSWORDLENGTH>>"] = $passwordlength;

    /* �ե�����̾�Ѵ�ʸ�������� */
    $html_strcode = "";
    foreach ($str_code as $code) {
        $selected = "";
        /* ���פ��������� */
        if (isset($samma_conf["strcode"]) === TRUE &&
              $samma_conf["strcode"] != "") {
            if ($code == $samma_conf["strcode"]) {
                $selected = "selected";
            } 
        } else {
            /* ���åȤ���Ƥ��ʤ����ϥǥե���� */
            if ($code == DEF_CODE) {
                $selected = "selected";
            }
        }
        $html_strcode .= "<option value=\"$code\" $selected>$code";
        $tag["<<HTML_STRCODE>>"] = $html_strcode;
    }

    /* �ǥե���Ƚ��� */
    $enc_on = "";
    $enc_off = "checked";
    if (isset($samma_conf["defaultencryption"]) === TRUE && 
              $samma_conf["defaultencryption"] != "") {
        if ($samma_conf["defaultencryption"] == "yes") {
            $enc_on = "checked";
            $enc_off = "";
        }
    }
    $tag["<<ENC_ON>>"] = $enc_on;
    $tag["<<ENC_OFF>>"] = $enc_off;

    /* �ǥե���ȥѥ���� */
    $defaultpassword = "";
    if (isset($samma_conf["defaultpassword"]) === TRUE && 
              $samma_conf["defaultpassword"] != "") {
        $defaultpassword = escape_html($samma_conf["defaultpassword"]);
    }
    $tag["<<DEFAULTPASSWORD>>"] = $defaultpassword;

    /* �桼���������� */
    $user_on = "";
    $user_off = "checked";
    $disabled = "disabled";
    if (isset($samma_conf["userpolicy"]) === TRUE && 
              $samma_conf["userpolicy"] != "") {
        if ($samma_conf["userpolicy"] == "yes") {
            $user_on = "checked";
            $user_off = "";
            $disabled = "";
        }
    }
    $tag["<<USER_ON>>"] = $user_on;
    $tag["<<USER_OFF>>"] = $user_off;
    $tag["<<DISABLED>>"] = $disabled;

    /* LDAP�����С��ݡ��� */
    $ldapserver = "";
    $ldapport = "";
    if (isset($samma_conf["ldapuri"]) === TRUE && 
              $samma_conf["ldapuri"] != "") {
        /* ��://�פ��ڤ�ʬ�� */
        $uri = explode("://", $samma_conf["ldapuri"]);
        /* IP�ȥݡ��Ȥ��ڤ�ʬ�� */
        $serverdata = explode(":", $uri[1]);
        /* ��Ǽ(�ݡ��Ȥ������Ρ�/�פ���) */
        $ldapserver = escape_html($serverdata[0]);
        $ldapport = escape_html(rtrim($serverdata[1], "/"));
    }
    $tag["<<LDAPSERVER>>"] = $ldapserver;
    $tag["<<LDAPPORT>>"] = $ldapport;

    /* LDAP�١���DN */
    $ldapbasedn = "";
    if (isset($samma_conf["ldapbasedn"]) === TRUE && 
              $samma_conf["ldapbasedn"] != "") {
        $ldapbasedn = escape_html($samma_conf["ldapbasedn"]);
    }
    $tag["<<LDAPBASEDN>>"] = $ldapbasedn;

    /* LDAP�Х����DN */
    $ldapbinddn = "";
    if (isset($samma_conf["ldapbinddn"]) === TRUE && 
              $samma_conf["ldapbinddn"] != "") {
        $ldapbinddn = escape_html($samma_conf["ldapbinddn"]);
    }
    $tag["<<LDAPBINDDN>>"] = $ldapbinddn;

    /* LDAP�Х���ɥѥ���� */
    $ldapbindpassword = "";
    if (isset($samma_conf["ldapbindpassword"]) === TRUE && 
              $samma_conf["ldapbindpassword"] != "") {
        $ldapbindpassword = escape_html($samma_conf["ldapbindpassword"]);
    }
    $tag["<<LDAPBINDPASSWORD>>"] = $ldapbindpassword;

    /* LDAP�ե��륿 */
    $ldapfilter = "";
    if (isset($samma_conf["ldapfilter"]) === TRUE && 
              $samma_conf["ldapfilter"] != "") {
        $ldapfilter = escape_html($samma_conf["ldapfilter"]);
    }
    $tag["<<LDAPFILTER>>"] = $ldapfilter;

    /* ��ĥ��DB����/�ѥ� */
    $html_extension = "";
    foreach ($db_types as $e_type) {
        $e_file = "";
        $type = "";
        if (isset($samma_conf["extensiondb"]) === TRUE &&
            $samma_conf["extensiondb"] != "") {
            $extension = explode(":", $samma_conf["extensiondb"], 2);
            $type = $extension[0];
            $e_file = escape_html($extension[1]);
        }
        $selected = "";
        /* ���פ��������� */
        if ($e_type == $type) {
            $selected = "selected";
        }
        $html_extension .= "<option value=\"$e_type\" $selected>$e_type";
        $tag["<<HTML_EXTENSION>>"] = $html_extension;

    }
    $tag["<<E_FILE>>"] = $e_file;

    /* ���ޥ��DB����/�ѥ� */
    $html_command = "";
    foreach ($db_types as $e_type) {
        $com_file = "";
        $type = "";
        if (isset($samma_conf["commanddb"]) === TRUE &&
            $samma_conf["commanddb"] != "") {
            $command = explode(":", $samma_conf["commanddb"], 2);
            $type = $command[0];
            $com_file = escape_html($command[1]);
        }
        $selected = "";
        /* ���פ��������� */
        if ($e_type == $type) {
            $selected = "selected";
        }
        $html_command .= "<option value=\"$e_type\" $selected>$e_type";
        $tag["<<HTML_COMMAND>>"] = $html_command;

    }
    $tag["<<COM_FILE>>"] = $com_file;


    /* ����˥ѥ������������ */
    $passnotice_disabled = "disabled";
    $passnotice_sender = "";
    $passnotice_rcpt = "";
    $passnotice_both = "";
    
    /* ����˥ѥ�������ε�ǽ��ͭ���ˤʤäƤ����� */
    if (is_active_plugin("pluginpassnoticeactive")) {

        $passnotice_disabled = "";
 
        /* passwordnotice���ܤ����åȤ��Ƥ����� */
        if (isset($samma_conf["passwordnotice"])) {
            if ($samma_conf["passwordnotice"] == "0") {
                $passnotice_sender = "checked";
            } elseif ($samma_conf["passwordnotice"] == "1") {
                $passnotice_rcpt = "checked";
            } elseif ($samma_conf["passwordnotice"] == "2") {
                $passnotice_both = "checked";
            } else {
                $passnotice_sender = "checked";
            }
        } else {
            $passnotice_sender = "checked";
        }
    }

    $tag["<<PLUGINPASSNOTICEACTIVE>>"] = $passnotice_disabled;
    $tag["<<PASSNOTICE_SENDER>>"] = $passnotice_sender;
    $tag["<<PASSNOTICE_RCPT>>"] = $passnotice_rcpt;
    $tag["<<PASSNOTICE_BOTH>>"] = $passnotice_both;

    /* subjectsw�ɲõ�ǽ */
    $output_pass_log = "���Ϥ��ʤ�";
    $subjectsw_disable = "disabled";
    $useaddmessageheader_on = "";
    $useaddmessageheader_off = "checked";
    $messagetmpljppath = "";
    $messagetmplenpath = "";
    $messagetmplbothpath = "";
    $useencryptsubject_on = "";
    $useencryptsubject_off = "checked";
    $subjectencryptstringjp = "";
    $subjectencryptstringen = "";

    /* subjectsw�ɲõ�ǽ��ͭ���ˤʤäƤ����� */
    if (is_active_plugin("pluginsubjectswactive")) {

        $output_pass_log = "���Ϥ���";
        $subjectsw_disable = "";
 
        /* �᡼����ʸ���귿ʸ�������������� */
        if (isset($samma_conf["useaddmessageheader"])) {
            if ($samma_conf["useaddmessageheader"] === "yes") {
                $useaddmessageheader_on = "checked";
                $useaddmessageheader_off = "";
            }
        }

        /* ��ʸ�ɵ��Υե�����ѥ�(���ܸ�) */
        if (isset($samma_conf["messagetmpljppath"])) {
            $messagetmpljppath = escape_html($samma_conf["messagetmpljppath"]);
        }

        /* ��ʸ�ɵ��Υե�����ѥ�(�Ѹ�) */
        if (isset($samma_conf["messagetmplenpath"])) {
            $messagetmplenpath = escape_html($samma_conf["messagetmplenpath"]);
        }

        /* ��ʸ�ɵ��Υե�����ѥ�(ξ��) */
        if (isset($samma_conf["messagetmplbothpath"])) {
            $messagetmplbothpath = escape_html($samma_conf["messagetmplbothpath"]);
        }

        /* ��̾Ƚ��Ź沽������ */
        if (isset($samma_conf["useencryptsubject"])) {
            if ($samma_conf["useencryptsubject"] === "yes") {
                $useencryptsubject_on = "checked";
                $useencryptsubject_off = "";
            }
        }

        /* ��̾Ƚ��ʸ����(���ܸ�) */
        if (isset($samma_conf["subjectencryptstringjp"])) {
            $subjectencryptstringjp = escape_html($samma_conf["subjectencryptstringjp"]);
        }

        /* ��̾Ƚ��ʸ����(�Ѹ�) */
        if (isset($samma_conf["subjectencryptstringen"])) {
            $subjectencryptstringen = escape_html($samma_conf["subjectencryptstringen"]);
        }
    }

    $tag["<<PLUGINSUBJECTSWACTIVE>>"] = $subjectsw_disable;
    $tag["<<OUTPUTPASSTOLOG>>"] = $output_pass_log;
    $tag["<<USERADDMESSAGEHEADER_ON>>"] = $useaddmessageheader_on;
    $tag["<<USERADDMESSAGEHEADER_OFF>>"] = $useaddmessageheader_off;
    $tag["<<MESSAGETMPLJPPATH>>"] = $messagetmpljppath;
    $tag["<<MESSAGETMPLENPATH>>"] = $messagetmplenpath;
    $tag["<<MESSAGETMPLBOTHPATH>>"] = $messagetmplbothpath;
    $tag["<<USERENCRYPTSUBJECT_ON>>"] = $useencryptsubject_on;
    $tag["<<USERENCRYPTSUBJECT_OFF>>"] = $useencryptsubject_off;
    $tag["<<SUBJECTENCRYPTSTRINGJP>>"] = $subjectencryptstringjp;
    $tag["<<SUBJECTENCRYPTSTRINGEND>>"] = $subjectencryptstringen;

    /* ����������ź�եե������Content-Type */
    $zipattachmentcontenttype = "";
    if (isset($samma_conf["zipattachmentcontenttype"]) === TRUE &&
              $samma_conf["zipattachmentcontenttype"] != "") {
        $zipattachmentcontenttype = escape_html($samma_conf["zipattachmentcontenttype"]);
    }
    $tag["<<ZIP_ATTCHMENT_CONTENT_TYPE>>"] = $zipattachmentcontenttype;
    
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

    /* ����Ϣ���ѥѥ���� */
    /* �������å� */
    if ($data["commandpass"] == "") {
        $err_msg = "����Ϣ���ѥѥ���ɤ����Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* ���������å���*/
    if (check_samma_pass($data["commandpass"]) !== TRUE) {
        $err_msg = "����Ϣ����" . $err_msg;
        return FALSE;
    }


    /* syslog�ե�����ƥ� */
    foreach ($logfacility as $log) {
        if ($log == $data["syslogfacility"]) {
            $data["syslogfacility"] = $log;
        }
    }


    /* �����Ǽ�ǥ��쥯�ȥ� */
    /* �������å� */
    if ($data["encryptiontmpdir"] == "") {
        $err_msg = "�����Ǽ�ǥ��쥯�ȥ꤬���Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* ���������å���*/
    if (check_dir($data["encryptiontmpdir"]) !== TRUE) {
        $err_msg = "�����Ǽ" . $err_msg;
        return FALSE;
    }


    /* zip���ޥ�ɥѥ� */
    /* �������å� */
    if ($data["zipcommand"] == "") {
        $err_msg = "���������ޥ�ɥѥ������Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* ���������å���*/
    if (check_command($data["zipcommand"]) !== TRUE) {
        $err_msg = "zip" . $err_msg;
        return FALSE;
    }


    /* zip���ޥ�ɥ��ץ���� */
    /* �������å� */
    if ($data["zipcommandopt"] != "") {
        /* ���������å���*/
        if (check_str($data["zipcommandopt"], CHECK_STR4) !== TRUE) {
            $err_msg = "���������ޥ�ɥ��ץ�����" . $err_msg;
            return FALSE;
        }
    }


    /* �����ɥᥤ��DB����/�ѥ� */
    /* �������å� */
    if ($data["sd_dbfile"] == "") {
        $data["senderdb"] = "";
    } else {
        /* db̾�����å�(".db"���Ĥ��Ƥ��뤫) */
        if (substr($data["sd_dbfile"], -3, 3) != ".db") {
            $data["sd_dbfile"] .= ".db";
            $data["senderdb"] .= ".db";
        }
        /* ���������å�(�ʤ���к���) */
        if (check_db($data["sd_dbfile"], $data["sd_dbtype"]) === FALSE) {
            $err_msg = "�����ɥᥤ��" . $err_msg;
            return FALSE;
        }
    }


    /* ������DB����/�ѥ� */
    /* �������å� */
    if ($data["rp_dbfile"] == "") {
        $data["rcptdb"] = "";
    } else {
        /* db̾�����å�(".db"���Ĥ��Ƥ��뤫) */
        if (substr($data["rp_dbfile"], -3, 3) != ".db") {
            $data["rp_dbfile"] .= ".db";
            $data["rcptdb"] .= ".db";
        }

        /* ���������å�(�ʤ���к���) */
        if (check_db($data["rp_dbfile"], $data["rp_dbtype"]) === FALSE) {
            $err_msg = "������" . $err_msg;
            return FALSE;
        }
    }

    /* �ƥ�ץ졼�� */
    /* �������å� */
    if ($data["templatepath"] == "") {
        $err_msg = "���Υ᡼��ƥ�ץ졼�ȥѥ������Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* ���������å�(�ʤ���к���) */
    if (check_tmpl($data["templatepath"], DEF_TMPL) === FALSE) {
        return FALSE;
    }

    /* ������ƥ�ץ졼�� */
    if ($data["rcpttemplatepath"] !== "") {
        /* ���������å�(�ʤ���к���) */
        if (check_tmpl($data["rcpttemplatepath"], DEF_RCPT_TMPL) === FALSE) {
            return FALSE;
        }
    }

    /* sendmail���ޥ�ɥѥ�/���ץ���� */
    /* �������å� */
    if ($data["sendmailcommand"] == "") {
        $err_msg = "sendmail���ޥ�ɥѥ�/���ץ�������Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* ���������å���*/
    if (check_str($data["sendmailcommand"], CHECK_STR4) !== TRUE) {
        $err_msg = "sendmail���ޥ�ɥѥ�/���ץ�����" . $err_msg;
        return FALSE;
    }
    /* ���ޥ�ɥ����å� */
    $com = explode(" ", $data["sendmailcommand"], 2); 
    if (check_command($com[0]) !== TRUE) {
        $err_msg = "sendmail" . $err_msg;
        return FALSE;
    }


    /* �Ź沽ZIP�ե�����ե����ޥå� */
    /* �������å� */
    if ($data["zipfilename"] == "") {
        $err_msg = "�����Ԥ��Ϥ��᡼���ź�եե�����̾�ե����ޥåȤ����Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* ���������å���*/
    if (check_str($data["zipfilename"], CHECK_STR3) !== TRUE) {
        $err_msg = "���������ޥ�ɥ��ץ�����" . $err_msg;
        return FALSE;
    }


    /* �����¸�ǥ��쥯�ȥ� */
    /* �������å� */
    if ($data["mailsavetmpdir"] == "") {
        $err_msg = "�����¸�ǥ��쥯�ȥ꤬���Ϥ���Ƥ��ޤ���";
        return FALSE;
    }
    /* ���������å���*/
    if (check_dir($data["mailsavetmpdir"]) !== TRUE) {
        $err_msg = "�����¸" . $err_msg;
        return FALSE;
    }


    /* �ѥ����ʸ���� */
    if ($data["passwordlength"] != "") {
        if (is_numeric($data["passwordlength"]) === FALSE) {
            $err_msg = "�ѥ����ʸ�����η����������Ǥ���";
            return FALSE;
        } elseif ($data["passwordlength"] < PASS_MIN || $data["passwordlength"] > PASS_MAX) {
            $err_msg = "�ѥ����ʸ�����η����������Ǥ���";
            return FALSE;
        }
    }

    /* �ե�����̾�Ѵ�ʸ�������� */
    foreach ($str_code as $code) {
        if ($code == $data["strcode"]) {
            $data["strcode"] = $code;
        }
    }


    /* �ǥե���ȥѥ���� */
    /* �������å� */
    if ($data["defaultpassword"] != "") {
        /* ���������å���*/
        if (check_samma_pass($data["defaultpassword"]) !== TRUE) {
            $err_msg = "�ǥե����" . $err_msg;
            return FALSE;
        }
    }

    /* �桼���������꤬ͭ���λ� */
    if ($data["userpolicy"] == "yes") {

        /* LDAP������ */
        /* �������å� */
        if ($data["ldapserver"] == "") {
            $err_msg = "LDAP�����Ф����Ϥ���Ƥ��ޤ���";
            return FALSE;
        }
        /* ���������å���*/
        if (check_ip_addr($data["ldapserver"]) !== TRUE) {
            $err_msg = "LDAP�����Ф�" . $err_msg;
            return FALSE;
        }


        /* LDAP�ݡ��� */
        /* �������å� */
        if ($data["ldapport"] == "") {
            $err_msg = "LDAP�ݡ��Ȥ����Ϥ���Ƥ��ޤ���";
            return FALSE;
        }
        /* ���������å���*/
        if (check_port($data["ldapport"]) === FALSE) {
            $err_msg = "LDAP�ݡ��Ȥη����������Ǥ���";
            return FALSE;
        }


        /* LDAP�١���DN */
        /* �������å� */
        if ($data["ldapbasedn"] == "") {
            $err_msg = "LDAP�١���DN�����Ϥ���Ƥ��ޤ���";
            return FALSE;
        }
        /* ���������å���*/
        if (check_str($data["ldapbasedn"], CHECK_STR6) !== TRUE) {
            $err_msg = "LDAP�١���DN��" . $err_msg;
            return FALSE;
        }


        /* LDAP�Х����DN */
        /* �������å� */
        if ($data["ldapbinddn"] == "") {
            $err_msg = "LDAP�Х����DN�����Ϥ���Ƥ��ޤ���";
            return FALSE;
        }
        /* ���������å���*/
        if (check_str($data["ldapbinddn"], CHECK_STR6) !== TRUE) {
            $err_msg = "LDAP�Х����DN��" . $err_msg;
            return FALSE;
        }


        /* LDAP�Х���ɥѥ���� */
        /* �������å� */
        if ($data["ldapbindpassword"] == "") {
            $err_msg = "LDAP�Х���ɥѥ���ɤ����Ϥ���Ƥ��ޤ���";
            return FALSE;
        }
        /* ���������å���*/
        if (check_samma_pass($data["ldapbindpassword"]) !== TRUE) {
            $err_msg = "LDAP�Х����" . $err_msg;
            return FALSE;
        }


        /* LDAP�ե��륿 */
        /* �������å� */
        if ($data["ldapfilter"] == "") {
            $err_msg = "LDAP�ե��륿�����Ϥ���Ƥ��ޤ���";
            return FALSE;
        }
        /* ���������å���*/
        if (check_str($data["ldapfilter"], CHECK_STR3, FILTER_MIN, FILTER_MAX) !== TRUE) {
            $err_msg = "LDAP�ե��륿��" . $err_msg;
            return FALSE;
        }
    }

    /* ��ĥ��DB����/�ѥ� */
    /* �������å� */
    if ($data["ex_dbfile"] == "") {
        $data["extensiondb"] = "";
    } else {
        /* db̾�����å�(".db"���Ĥ��Ƥ��뤫) */
        if (substr($data["ex_dbfile"], -3, 3) != ".db") {
            $data["ex_dbfile"] .= ".db";
            $data["extensiondb"] .= ".db";
        }

        /* ���������å�(�ʤ���к���) */
        if (check_db($data["ex_dbfile"], $data["ex_dbtype"]) === FALSE) {
            $err_msg = "��ĥ��" . $err_msg;
            return FALSE;
        }
    }

    /* ���ޥ��DB����/�ѥ� */
    /* �������å� */
    if ($data["com_dbfile"] == "") {
        $data["commanddb"] = "";
    } else {
        /* db̾�����å�(".db"���Ĥ��Ƥ��뤫) */
        if (substr($data["com_dbfile"], -3, 3) != ".db") {
            $data["com_dbfile"] .= ".db";
            $data["commanddb"] .= ".db";
        }

        /* ���������å�(�ʤ���к���) */
        if (check_db($data["com_dbfile"], $data["com_dbtype"]) === FALSE) {
            $err_msg = "���ޥ��" . $err_msg;
            return FALSE;
        }
    }

    /* ����˥ѥ���ɤ����Τ��뵡ǽ��ͭ���ˤʤäƤ����� */
    if (is_active_plugin("pluginpassnoticeactive")) {
        /* �ѥ�������Τ����� */
        if ($data["passwordnotice"] !== "0" && 
            $data["passwordnotice"] !== "1" && 
            $data["passwordnotice"] !== "2") {
            $err_msg = "�ѥ�������Τ����꤬�����Ǥ���(" . $data['passwordnotice']. ")";
            return FALSE;
         }
    }

    /* subjectsw��ǽ��ͭ���ˤʤäƤ����� */
    if (is_active_plugin("pluginsubjectswactive")) {

        /* �᡼����ʸ���귿ʸ�������������� */
        if (($data["useaddmessageheader"] != "yes") && ($data["useaddmessageheader"] != "no")) {
            $err_msg = "�᡼����ʸ���귿ʸ�������������꤬�����Ǥ���(" . $data["useaddmessageheader"] . ")";
            return FALSE;
        }

        /* ��ʸ�ɵ��Υե�����ѥ� */
        $ret = check_messagetmplpath($data["messagetmpljppath"], 
                                     $data["messagetmplenpath"],
                                     $data["messagetmplbothpath"], $errormsg);
        if (!$ret) {
            $err_msg = $errormsg;
            return FALSE;
        }

        /* ��̾Ƚ��Ź沽������ */
        if (($data["useencryptsubject"] != "yes") && ($data["useencryptsubject"] != "no")) {
            $err_msg = "��̾Ƚ������������꤬�����Ǥ���(". $data["useencryptsubject"]. ")";
            return FALSE;
        }

        /* ��̾Ƚ��ʸ����(���ܸ�) */
        $ret = check_str($data["subjectencryptstringjp"], "[]{}()@!#$%&+*=-/:;.,?_", 0, 256);
        if (!$ret) {
            $err_msg = "��̾Ƚ��ʸ����(���ܸ�)�η����������Ǥ���(". $data["subjectencryptstringjp"]. ")";
            return FALSE;
        } 

        /* ��̾Ƚ��ʸ����(�Ѹ�) */
        $ret = check_str($data["subjectencryptstringen"], "[]{}()@!#$%&+*=-/:;.,?_", 0, 256);
        if (!$ret) {
            $err_msg = "��̾Ƚ��ʸ����(�Ѹ�)�η����������Ǥ���(". $data["subjectencryptstringen"]. ")";
            return FALSE;
        }
    }

    /* ����������ź�եե������Content-Type */
    if ($data["zipattachmentcontenttype"] !== "") {
        /* ���������å� */
        if (check_content_type($data["zipattachmentcontenttype"]) !== TRUE) {
            $err_msg = "����������ź�եե������Content-Type" . $err_msg;
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
 * check_tmpl
 *
 * �ƥ�ץ졼�ȥե���������å�
 *
 * [����]
 *       $filename	�ƥ�ץ졼�ȥե�����
 *       $df_tmpl	�ǥե���ȥƥ�ץ졼�ȥե�����
 *
 * [�֤���]
 *	TRUE		����
 *	FALSE		�۾�
 **********************************************************/
function check_tmpl($filename, $df_tmpl)
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

        /* �ƥ�ץ졼�ȥե�������� */
        $ret = make_def_tmpl($filename, $df_tmpl);
        if ($ret === FALSE) {
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
    /* commandpass */
    if (isset($data["commandpass"]) === TRUE) {
        $disp_data["commandpass"] = $data["commandpass"];
    }

    /* syslogfacility */
    if (isset($data["syslogfacility"]) === TRUE) {
        $disp_data["syslogfacility"] = $data["syslogfacility"];
    }

    /* encryptiontmpdir */
    if (isset($data["encryptiontmpdir"]) === TRUE) {
        $disp_data["encryptiontmpdir"] = $data["encryptiontmpdir"];
    }

    /* zipcommand */
    if (isset($data["zipcommand"]) === TRUE) {
        $disp_data["zipcommand"] = $data["zipcommand"];
    }

    /* zipcommandopt */
    if (isset($data["zipcommandopt"]) === TRUE) {
        $disp_data["zipcommandopt"] = $data["zipcommandopt"];
    }

    /* senderdb */
    if (isset($data["sd_dbfile"]) === TRUE) {
        $disp_data["sd_dbfile"] = $data["sd_dbfile"];
        $disp_data["sd_dbtype"] = $data["sd_dbtype"];
        $disp_data["senderdb"] = $data["sd_dbtype"] . ":" . $data["sd_dbfile"];
    }

    /* rcptdb */
    if (isset($data["rp_dbfile"]) === TRUE) {
        $disp_data["rp_dbfile"] = $data["rp_dbfile"];
        $disp_data["rp_dbtype"] = $data["rp_dbtype"];
        $disp_data["rcptdb"] = $data["rp_dbtype"] . ":" . $data["rp_dbfile"];
    }

    /* extensiondb */
    if (isset($data["ex_dbfile"]) === TRUE) {
        $disp_data["ex_dbfile"] = $data["ex_dbfile"];
        $disp_data["ex_dbtype"] = $data["ex_dbtype"];
        $disp_data["extensiondb"] = $data["ex_dbtype"] . ":" . $data["ex_dbfile"];
    }

    /* commanddb */
    if (isset($data["com_dbfile"]) === TRUE) {
        $disp_data["com_dbfile"] = $data["com_dbfile"];
        $disp_data["com_dbtype"] = $data["com_dbtype"];
        $disp_data["commanddb"] = $data["com_dbtype"] . ":" . $data["com_dbfile"];
    }

    /* templatepath */
    if (isset($data["templatepath"]) === TRUE) {
        $disp_data["templatepath"] = $data["templatepath"];
    }

    /* rcpttemplatepath */
    if (isset($data["rcpttemplatepath"]) === TRUE) {
        $disp_data["rcpttemplatepath"] = $data["rcpttemplatepath"];
    }

    /* sendmailcommand */
    if (isset($data["sendmailcommand"]) === TRUE) {
        $disp_data["sendmailcommand"] = $data["sendmailcommand"];
    }

    /* zipfilename */
    if (isset($data["zipfilename"]) === TRUE) {
        $disp_data["zipfilename"] = $data["zipfilename"];
    }

    /* mailsavetmpdir */
    if (isset($data["mailsavetmpdir"]) === TRUE) {
        $disp_data["mailsavetmpdir"] = $data["mailsavetmpdir"];
    }

    /* passwordlength */
    if (isset($data["passwordlength"]) === TRUE) {
        $disp_data["passwordlength"] = $data["passwordlength"];
    }

    /* strcode */
    if (isset($data["strcode"]) === TRUE) {
        $disp_data["strcode"] = $data["strcode"];
    }

    /* defaultencryption */
    if (isset($data["defaultencryption"]) === TRUE) {
        $disp_data["defaultencryption"] = $data["defaultencryption"];
    }

    /* defaultpassword */
    if (isset($data["defaultpassword"]) === TRUE) {
        $disp_data["defaultpassword"] = $data["defaultpassword"];
    }

    /* userpolicy */
    if (isset($data["userpolicy"]) === TRUE) {
        $disp_data["userpolicy"] = $data["userpolicy"];
    }

    /* userpolicy��"yes"�λ�����LDAP�ǡ������� */
    if (isset($data["userpolicy"]) === TRUE && $data["userpolicy"] == "yes") {

        /* ldapserver */
        if (isset($data["ldapserver"]) === TRUE) {
            $disp_data["ldapserver"] = $data["ldapserver"];
        }

        /* ldapport */
        if (isset($data["ldapport"]) === TRUE) {
            $disp_data["ldapport"] = $data["ldapport"];
        }

        /* server & port */
        if (isset($data["ldapserver"]) === TRUE && 
            isset($data["ldapport"]) === TRUE) { 
            $disp_data["ldapuri"] = "ldap://" . $data["ldapserver"] . ":" . $data["ldapport"] . "/";
        }

        /* ldapbasedn */
        if (isset($data["ldapbasedn"]) === TRUE) {
            $disp_data["ldapbasedn"] = $data["ldapbasedn"];
        }

        /* ldapbinddn */
        if (isset($data["ldapbinddn"]) === TRUE) {
            $disp_data["ldapbinddn"] = $data["ldapbinddn"];
        }

        /* ldapbindpassword */
        if (isset($data["ldapbindpassword"]) === TRUE) {
            $disp_data["ldapbindpassword"] = $data["ldapbindpassword"];
        }

        /* ldapfilter */
        if (isset($data["ldapfilter"]) === TRUE) {
            $disp_data["ldapfilter"] = $data["ldapfilter"];
        }
    }

    /* ����˥ѥ���ɤ����Τ��뵡ǽ��ͭ���ˤʤäƤ����� */
    if (is_active_plugin("pluginpassnoticeactive")) {
        /* passwordnotice */
        if (isset($data["passwordnotice"])) {
             $disp_data["passwordnotice"] = $data["passwordnotice"];
        }
    }

    /* subjectsw��ǽ��ͭ���ˤʤäƤ����� */
    if (is_active_plugin("pluginsubjectswactive")) {
        /* useaddmessageheader */
        if (isset($data["useaddmessageheader"])) {
            $disp_data["useaddmessageheader"] = $data["useaddmessageheader"];
        }

        /* messagetmpljppath */
        if (isset($data["messagetmpljppath"])) {
            $disp_data["messagetmpljppath"] = $data["messagetmpljppath"];
        }

        /* messagetmplenpath */
        if (isset($data["messagetmplenpath"])) {
            $disp_data["messagetmplenpath"] = $data["messagetmplenpath"];
        }

        /* messagetmplbothpath */
        if (isset($data["messagetmplbothpath"])) {
            $disp_data["messagetmplbothpath"] = $data["messagetmplbothpath"];
        }
  
        /* useencryptsubject */
        if (isset($data["useencryptsubject"])) {
            $disp_data["useencryptsubject"] = $data["useencryptsubject"];
        }

        /* subjectencryptstringjp */
        if (isset($data["subjectencryptstringjp"])) {
            $disp_data["subjectencryptstringjp"] = $data["subjectencryptstringjp"];
        }

        /* subjectencryptstringen */
        if (isset($data["subjectencryptstringen"])) {
            $disp_data["subjectencryptstringen"] = $data["subjectencryptstringen"];
        }
    }

    /* ����������ź�եե������Content-Type */
    if (isset($data["zipattachmentcontenttype"]) === TRUE) {
        $disp_data["zipattachmentcontenttype"] = $data["zipattachmentcontenttype"];
    }

    return;

}
/*********************************************************
 * make_def_tmpl
 *
 * �ƥ�ץ졼�ȥե��������
 *
 * [����]
 *       $filename      �ե�����̾
 *       $def_tmpl       �ǥե���ȥƥ�ץ졼�ȥե�����
 *
 * [�֤���]
 *      TRUE            ����
 *      FALSE           ����
 **********************************************************/
function make_def_tmpl($filename, $def_tmpl)
{
    global $err_msg;
    global $basedir;

    /* �ǥե���ȤΥƥ�ץ졼�ȥե�����ѥ����� */
    $def_tmpl = $basedir .  ETCDIR . $def_tmpl;

    /* �ǥե���ȤΥƥ�ץ졼�ȥե���������å� */
    if (is_readable_file($def_tmpl) === FALSE) {
        return FALSE;
    }

    /* �ե����륳�ԡ� */
    if (copy($def_tmpl, $filename) === FALSE) {
        $err_msg = "�ƥ�ץ졼�ȥե�����κ����˼��Ԥ��ޤ�����";
        return FALSE;
    }

    return TRUE;

}

/*********************************************************
 * is_active_plugin
 *
 * �ץ�����ͭ�����Ƥ��뤫�����å�����
 *
 * [����]
 *       $item          �������
 *
 * [�֤���]
 *      TRUE            ͭ��
 *      FALSE           ̵��
 **********************************************************/
function is_active_plugin($item)
{
    global $web_conf;

    if (isset($web_conf["postldapadmin"][$item])) {
        if ($web_conf["postldapadmin"][$item] === "yes") {
            return TRUE;
        }
    }

    return FALSE;
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
$ret = read_samma_conf($web_conf["postldapadmin"]["sammaconf"]);
if ($ret === FALSE) {
    syserr_display();
    exit (1);
}

/***********************************************************
 * main����
 **********************************************************/
/* �Ť�commandpass���� */
if (isset($samma_conf["commandpass"]) === TRUE) {
    $commandpass = $samma_conf["commandpass"];
}

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
        if (mod_samma_conf($samma_conf) === FALSE) {
            result_log(OPERATION . ":NG:" . $err_msg);
        /* ���� */
        } else {
            $err_msg = "����ե�����򹹿����ޤ�����";
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
