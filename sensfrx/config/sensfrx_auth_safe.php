<?php
// Emails
Configure::set(
    'Sensfrx.install.emails', [
        [
            'action' => 'Sensfrx.allow_approve_deny',
            'type' => 'client',
            'plugin_dir' => 'sensfrx',
            'tags' => '{name},{approve_url},{deny_url},{ip_address},{device_name},{location},{date_time}',
            'from' => 'admin@mydomain.com',
            'from_name' => 'Admin',
            'subject' => 'Unusual activity detected on this account',
            'text' => '
                Hi {name},
               There is some unusual activity detected on this account. Did you recently use this device {device_name} to perform some activity? Please open this link if this is you {approve_url} otherwise open this link {deny_url}
            ',
            'html' => '
                <p>Hi <strong>{name}<strong>,</strong></strong></p>
                <p>There is some unusual activity detected on this account. Did you recently use this device to perform some activity?</p>
                <table style="width: 100%; border-collapse: collapse;">
                <thead>
                <tr>
                <th style="border: 1px solid black;" scope="col">Device</th>
                <th style="border: 1px solid black;" scope="col">IP Address</th>
                <th style="border: 1px solid black;" scope="col">Location</th>
                <th style="border: 1px solid black;" scope="col">Time</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                <td style="border: 1px solid black;">{device_name}</td>
                <td style="border: 1px solid black;">{ip_address}</td>
                <td style="border: 1px solid black;">{location}</td>
                <td style="border: 1px solid black;">{date_time}</td>
                </tr>
                </tbody>
                </table>
                <p><br /><br /></p>
                <div style="text-align: center; margin: 0 auto;"><a style="color: #fff; background-color: #337ab7; margin-right: 10px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;" href="{approve_url}"> This was me </a> <a style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;" href="{deny_url}"> This was not me </a></div>
                <p><br /><br /></p>
            ',
            'lang_code' => 'en_us',
        ],
        [
            'action' => 'Sensfrx.reset_password',
            'type' => 'client',
            'plugin_dir' => 'sensfrx',
            'tags' => '{name},{password_reset_url},{ip_address},{contact},{client}',
            'from' => 'admin@mydomain.com',
            'from_name' => 'Admin',
            'subject' => 'Suspicious Login prevented - Reset {company.name} Password ',
            'text' => '
                Hi {name},
              This user account has been blocked due to recent suspicious activity. You are required to reset your password in order to gain access to your account again. To reset your password, please visit the url: {password_reset_url}. When you visit the link, you will have the opportunity to choose a new password.
            ',
            'html' => '
                <p>Dear {name},</p>
                <p>This user account has been blocked due to recent suspicious activity. You are required to reset your password in order to gain access to your account again.</p>
                <p>To reset your password, please visit the url below:<br /><a href="{password_reset_url}">{password_reset_url}</a></p>
                <p>When you visit the link above, you will have the opportunity to choose a new password.</p>
            ',
            'lang_code' => 'en_us',
        ]
    ]
);