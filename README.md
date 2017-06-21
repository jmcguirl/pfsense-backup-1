# pfsense-backup

Ziel dieses kleinen PHP-Projektes ist, ein automatisiertes Backup der pfSense-Konfiguration zu ermöglichen.

## Features

- Einfache Konfiguration über eine XML-Datei
- Backups können wahlweise per E-Mail versendet oder in einem Ordner gespeichert werden

## Requirements
- min. PHP 5.5 with cURL
- Irgendein Cronjob-Runner

## License

This software is distributed under the [GPL 3.0](http://www.gnu.org/licenses/gpl-3.0.html) license. Please read LICENSE for information on the software availability and distribution.

## Installation and Setup

Zunächst muss der Code aus diesem Repository auf einem Webserver deployed werden. Bitte achten Sie darauf, dass alle Unterordner von `/var/` vom Webserver-User beschrieben werden können.

Im Unterordner `/etc/` befindet sich eine Bespiel-Konfigurationsdatei namens `/etc/config.xml`. Einstellungen können darin vorgenommen werden. Es wird jedoch empfohlen, eine Kopie dieser Datei im gleichen Ordner mit dem Namen `/etc/config.local.xml` zu erstellen und die Einstellungen dort vorzunehmen.

Nach erfolgreicher Konfiguration kann das Script durch folgenden URL-Aufruf gestartet werden:
`https://localhost/pfsense-backup/cron.php`

Gemeldetete Fehler sind in der Regel auf mangelnde Dateiberechtigungen oder falsche Konfiguration zurückzuführen.

Für die regelmäßige Ausführung empfiehlt sich bspw. das Crontab-Modul eines Unix-Servers. Eine beispielhafte Konfiguration auf einem Linux-Server:

Bearbeiten der Cronjobs starten: `sudo crontab -e`

`0 3 * * * wget -q --spider https://localhost/pfsense-backup/cron.php`

Das Script wird nun täglich um 3:00 Uhr ausgeführt.

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