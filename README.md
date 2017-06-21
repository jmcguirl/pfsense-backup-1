# pfsense-backup

The goal of this little PHP project is to gain automated config backups from a pfsense box.

## Features

- Simple configuration via XML file
- Backups can be saved locally or may be sent to one or more e-mail-recipients

## Requirements
- min. PHP 5.5 with cURL
- some server running cron jobs

## License

This software is distributed under the [GPL 3.0](http://www.gnu.org/licenses/gpl-3.0.html) license. Please read LICENSE for information on the software availability and distribution.

## Installation and Setup

The code in this repo needs to be deployed to a web server. Make sure user write permiussion for `/var/` and subfolders are set.

The subfolder `/etc/` contains a sample config file called `/etc/config.xml`. Settings can be made there, although it is recommended to make a copy called `/etc/config.local.xml` in the same folder and make settings there.

With options in the config-file set, the script can be run by visiting this URL:
`https://localhost/pfsense-backup/cron.php`

Errors are usually either permission- or config related.

For regular runs using the crontab-module of a Unix server is recommended. Sample config for a Linux server:

`sudo crontab -e`

`0 3 * * * wget -q --spider https://localhost/pfsense-backup/cron.php`

The script will then be run every day at 3AM

## Configuration

General script configuration:
```xml
<general>
    <charset>UTF-8</charset>
    <timezone>Europe/Berlin</timezone>
    <heartbeat_url>http://your-heartbeat-tracker.example.com/pulse/pfsense-backup</heartbeat_url>
    <!-- A ping will be send to this url after successful backup. Leave empty if you like. -->
</general>
```

Main script behavior configuration:
```xml
<backup>
    <mode>mail</mode> <!-- mail or store -->
    <pfsense_address>10.0.0.1</pfsense_address> <!-- IP address or hostname -->
    <pfsense_protocol>http</pfsense_protocol> <!-- HTTP or HTTPS -->
    <credentials>
        <username>admin</username>
        <password>eW91cnNlY3JldHB3</password> <!-- You pfsense password BASE64 encoded -->
    </credentials>
    
    <!-- pfSense form settings below (as in Diagnostics > Backup and Restore -->
    <backuparea>all</backuparea>
    <nopackages>no</nopackages>
    <donotbackuprrd>yes</donotbackuprrd>
    <encrypt>no</encrypt>
    <encrypt_password />
    
    <!-- Configuration for different modes -->
    <mode_configs>
        <mail>
            <recipient_addresses>admin@example.com,admin2@example.com</recipient_addresses> <!-- Comma separated list of mail recipients -->
        </mail>
        <store>
            <target_directory><![CDATA[/var/www/html/pfsense-backup/var/backups/]]></target_directory> <!-- Please use an absolute path here if possible -->
            <override_file>1</override_file> <!-- 1 or 0 - if 1 the backup file will be overwritten with each script execution -->
        </store>
    </mode_configs>
</backup>
```

Die Outbox-Konfiguration ist nicht zwingend notwendig. Über dieses Postfach werden sowohl die Backups (sofern E-Mail-Versand gewünscht) als auch Log-Nachrichten versendet.
```xml
<outbox>
    <sender_address>pfsense-backup@example.com</sender_address>
    <sender_name>pfSense-Backup</sender_name>
    <smtp>
        <host>smtp.example.com</host>
        <port>25</port>
        <encryption>tls</encryption>
        <auth>1</auth> <!-- 1 or 0 - if 1 please provide credentials below -->
        <user>pfsense-backup@example.com</user>
        <password>yoursecretmailpwd</password>
    </smtp>
</outbox>
```

Log settings:
```xml
<log>
    <loglevel>warning</loglevel> <!-- If log message has level warning or error, it will be logged in a file -->
    <notifylevel>error</notifylevel> <!-- If log message has level error, an email will be sent -->
    <recipients>admin@example.com</recipients> <!-- Comma separated list of mail recipients -->
    <ident>PFSENSE-BACKUP</ident> <!-- You are going to see this in the mail subjects as a prefix in brackets -->
    <mail_header><![CDATA[]]></mail_header> <!-- Place your custom mail templates header here. -->
    <mail_footer><![CDATA[]]></mail_footer> <!-- ... and your footer. -->
</log>
```
