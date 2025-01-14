<?php
/**
 * en_us language for the Sensfrx plugin.
 */
// Basics
$lang['SensfrxPlugin.name'] = 'Sensfrx';
$lang['SensfrxPlugin.description'] = 'This module Provides user behavior, online fraud attack trained models to enable risk-based authentication to stop cybercriminal access';
$lang['SensfrxPlugin.nav.home'] = 'Incident Policies';
$lang['SensfrxPlugin.nav.webhook'] = 'Web Hook Update';
$lang['SensfrxPlugin.deny.logout.message_blocked'] = 'Your account has been blocked on this device. Please contact administrator on this email {email}.';
$lang['SensfrxPlugin.deny.logout.message_resetpassword'] = 'Your account has been blocked on this device. Please check your email for instructions.';
$lang['SensfrxPlugin.device.approve.message'] = 'Device successfully approved.';
$lang['SensfrxPlugin.device.approve.error'] = 'Unable to validate device.';
$lang['SensfrxPlugin.device.deny.message'] = 'Device successfully denied.';
$lang['SensfrxPlugin.device.deny.error'] = 'Unable to validate device.';
$lang['SensfrxPlugin.redirect.message'] = 'Click <a href="{link}" style="color: blue;">here</a> if you are not redirected automatically.';

/* nav bar */
$lang['SensfrxPlugin.nav.tab1'] = 'General';
$lang['SensfrxPlugin.nav.tab2'] = 'Order Review';
$lang['SensfrxPlugin.nav.tab10'] = 'Account Review';
$lang['SensfrxPlugin.nav.tab3'] = 'Activity';
$lang['SensfrxPlugin.nav.tab4'] = 'Validation Rules';
$lang['SensfrxPlugin.nav.tab5'] = 'Policies Settings';
$lang['SensfrxPlugin.nav.tab6'] = 'Notifications/Alerts';
$lang['SensfrxPlugin.nav.tab7'] = 'License Information';
$lang['SensfrxPlugin.nav.tab8'] = 'Account & Privacy';
$lang['SensfrxPlugin.nav.tab9'] = 'Profile';

/* Dashboard/genral tab */
$lang['SensfrxPlugin.general.heading'] = 'General Information';
$lang['SensfrxPlugin.general.heading1'] = 'Account Security Analytics';
$lang['SensfrxPlugin.general.heading2'] = 'Total Logins';
$lang['SensfrxPlugin.general.heading3'] = 'Denied Logins';
$lang['SensfrxPlugin.general.heading4'] = 'Challenged Logins';
$lang['SensfrxPlugin.general.heading5'] = 'Allowed Logins';
$lang['SensfrxPlugin.general.heading6'] = 'Transaction Fraud Analytics';
$lang['SensfrxPlugin.general.heading7'] = 'Total Transactions';
$lang['SensfrxPlugin.general.heading8'] = 'Denied Transactions';
$lang['SensfrxPlugin.general.heading9'] = 'Challenged Transactions';
$lang['SensfrxPlugin.general.heading10'] = 'Allowed Transactions';
$lang['SensfrxPlugin.general.heading11'] = 'New Account Analytics';
$lang['SensfrxPlugin.general.heading12'] = 'Total New Accounts';
$lang['SensfrxPlugin.general.heading13'] = 'Denied New Accounts';
$lang['SensfrxPlugin.general.heading14'] = 'Challenged New Accounts';
$lang['SensfrxPlugin.general.heading15'] = 'Allowed New Accounts';
$lang['SensfrxPlugin.general.heading16'] = 'Click here';
$lang['SensfrxPlugin.general.heading17'] = 'To access the dashboard for more data-driven insights and captivating graphical representation';

/* policies settings tab */
$lang['SensfrxPlugin.Policies.tab1'] = 'Account Security Policies';
$lang['SensfrxPlugin.Policies.tab2'] = 'Transaction Security Policies';
$lang['SensfrxPlugin.Policies.tab3'] = 'Registration Security Setting';
$lang['SensfrxPlugin.Policies.tab4'] = 'Webhook Security Setting';

/* order review tab */
$lang['SensfrxPlugin.order.heading'] = 'Order Review ';
$lang['SensfrxPlugin.order.table.heading1'] = '#ID';
$lang['SensfrxPlugin.order.table.heading2'] = 'Order ID';
$lang['SensfrxPlugin.order.table.heading3'] = 'Email';
$lang['SensfrxPlugin.order.table.heading4'] = 'Risk Score';
$lang['SensfrxPlugin.order.table.heading5'] = 'Date Created';
$lang['SensfrxPlugin.order.table.heading6'] = 'Details';
$lang['SensfrxPlugin.order.table.heading7'] = 'Action';

/* account review tab */
$lang['SensfrxPlugin.account.review.heading'] = 'Account Review ';
$lang['SensfrxPlugin.account.review.table.heading1'] = '#ID';
$lang['SensfrxPlugin.account.review.table.heading2'] = 'Name';
$lang['SensfrxPlugin.account.review.table.heading3'] = 'Email';
$lang['SensfrxPlugin.account.review.table.heading4'] = 'Risk Score';
$lang['SensfrxPlugin.account.review.table.heading5'] = 'Date Created';
$lang['SensfrxPlugin.account.review.table.heading6'] = 'Details';
$lang['SensfrxPlugin.account.review.table.heading7'] = 'Action';

/* Activity tab */
$lang['SensfrxPlugin.activity.heading'] = 'Activity';
$lang['SensfrxPlugin.activity.table.heading1'] = '#ID';
$lang['SensfrxPlugin.activity.table.heading2'] = 'Activity';
$lang['SensfrxPlugin.activity.table.heading3'] = 'Text';
$lang['SensfrxPlugin.activity.table.heading4'] = 'Time';

/* Notifications tab */
$lang['SensfrxPlugin.notification.heading'] = 'Enable Real-Time Alerts';
$lang['SensfrxPlugin.notification.heading1'] = 'Enable Email Notifications:';
$lang['SensfrxPlugin.notification.heading2'] = 'Emails will be triggered when the device score reaches the selected threshold.';
$lang['SensfrxPlugin.notification.heading3'] = 'Suspicious (Risk Score: 30)';
$lang['SensfrxPlugin.notification.heading4'] = 'Suspicious (Risk Score: 40)';
$lang['SensfrxPlugin.notification.heading5'] = 'Suspicious (Risk Score: 50)';
$lang['SensfrxPlugin.notification.heading6'] = 'Suspicious (Risk Score: 60)';
$lang['SensfrxPlugin.notification.heading7'] = 'Suspicious (Risk Score: 70)';
$lang['SensfrxPlugin.notification.heading8'] = 'Suspicious (Risk Score: 80)';
$lang['SensfrxPlugin.notification.heading9'] = 'Compromised (Risk Score: 90)';
$lang['SensfrxPlugin.notification.heading10'] = 'Compromised (Risk Score: 100)';
$lang['SensfrxPlugin.notification.heading11'] = 'Select Risk Threshold:';
$lang['SensfrxPlugin.notification.heading12'] = 'Email Address:';
$lang['SensfrxPlugin.notification.heading13'] = 'Configure how to get notified when a login exceeds the specified risk threshold. You can also let your users resolve these alerts by activating Security Notifications.';

/* License tab */
$lang['SensfrxPlugin.license.heading'] = 'Account Information';
$lang['SensfrxPlugin.license.heading1'] = 'Field';
$lang['SensfrxPlugin.license.heading2'] = 'Value';
$lang['SensfrxPlugin.license.heading3'] = 'Status';
$lang['SensfrxPlugin.license.heading4'] = 'Plan';
$lang['SensfrxPlugin.license.heading5'] = 'Available Credit';
$lang['SensfrxPlugin.license.heading6'] = 'Start Date';
$lang['SensfrxPlugin.license.heading7'] = 'Renewal Date';
$lang['SensfrxPlugin.license.heading8'] = 'Note: Subscription auto-renews unless canceled before the billing period ends.';
$lang['SensfrxPlugin.license.heading9'] = 'Need support? Contact us at';
$lang['SensfrxPlugin.license.heading10'] = 'info@sensfrx.ai';

/* Account & Privacy tab */
$lang['SensfrxPlugin.account.heading'] = 'Manage Your Account Information';
$lang['SensfrxPlugin.account.heading1'] = 'Email:';
$lang['SensfrxPlugin.account.heading2'] = 'Data Protection and Sharing';
$lang['SensfrxPlugin.account.heading3'] = 'Your data is securely stored and protected from unauthorized access.<br>We do not share your data with any third parties.';
$lang['SensfrxPlugin.account.heading4'] = 'Compliance';
$lang['SensfrxPlugin.account.heading5'] = 'We are committed to complying with all relevant privacy regulations, including IT ACT, 2000 Compliance.';
$lang['SensfrxPlugin.account.heading6'] = 'User Consent';
$lang['SensfrxPlugin.account.heading7'] = 'By using our fraud detection product, you give consent to the data collation. If you want to withdraw your consent and delete your data, please contact us at info.sensfrx.ai.';
$lang['SensfrxPlugin.account.heading8'] = 'I agree to the terms and conditions';
$lang['SensfrxPlugin.account.heading9'] = 'Please accept the terms and conditions.';
$lang['SensfrxPlugin.account.heading10'] = 'Please enter your email and check the box to agree to the terms and conditions.';
$lang['SensfrxPlugin.account.heading11'] = 'Update';

/*  tab */
$lang['SensfrxPlugin.profile_info.heading'] = 'Edit';
$lang['SensfrxPlugin.profile_info.heading1'] = 'Update Your Information';
$lang['SensfrxPlugin.profile_info.heading2'] = 'Full Name:';
$lang['SensfrxPlugin.profile_info.heading3'] = 'Please enter your first and last name only.';
$lang['SensfrxPlugin.profile_info.heading4'] = 'Gender:';
$lang['SensfrxPlugin.profile_info.heading5'] = 'Male';
$lang['SensfrxPlugin.profile_info.heading6'] = 'Female';
$lang['SensfrxPlugin.profile_info.heading7'] = 'Email Address:';
$lang['SensfrxPlugin.profile_info.heading8'] = 'Phone Number:';
$lang['SensfrxPlugin.profile_info.heading9'] = 'Select Timezone:';
$lang['SensfrxPlugin.profile_info.heading10'] = 'Brand Name:';
$lang['SensfrxPlugin.profile_info.heading11'] = 'Brand Website:';
$lang['SensfrxPlugin.profile_info.heading12'] = 'Organization Name:';
$lang['SensfrxPlugin.profile_info.heading13'] = 'Please fill in all required fields.';
$lang['SensfrxPlugin.profile_info.heading14'] = 'Submit';
$lang['SensfrxPlugin.profile_info.heading15'] = 'Cancel';
$lang['SensfrxPlugin.profile_info.heading16'] = 'Outh 2.0 Authentication Settings.';
$lang['SensfrxPlugin.profile_info.heading17'] = 'Client Name: ';
$lang['SensfrxPlugin.profile_info.heading18'] = 'Sex: ';
$lang['SensfrxPlugin.profile_info.heading19'] = 'Email: ';
$lang['SensfrxPlugin.profile_info.heading20'] = 'Phone: ';
$lang['SensfrxPlugin.profile_info.heading21'] = 'Timezone: ';
$lang['SensfrxPlugin.profile_info.heading22'] = 'Brand Name: ';
$lang['SensfrxPlugin.profile_info.heading23'] = 'Brand URL: ';
$lang['SensfrxPlugin.profile_info.heading24'] = 'Organization Name: ';
$lang['SensfrxPlugin.profile_info.heading25'] = '';

