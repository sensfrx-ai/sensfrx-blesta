# Sensfrx Blesta Extension

The **Sensfrx Blesta Extension** enhances your Blesta application with advanced fraud prevention capabilities, including real-time risk scores, detailed analytics, instant alerts, and customizable policies. These features ensure your business remains secure and protected from fraud.

---

## Features
- Real-time risk scoring
- Comprehensive fraud analytics
- Instant fraud alerts
- Customizable fraud prevention policies
- Easy integration with Blesta

---

## Getting Started

### Step 1: Create and Set Up Your Sensfrx Account
1. Register for a Sensfrx account.
2. Obtain your **Property ID** and **Property Secret**:
   - **Property ID**: Represents the domain name to integrate Sensfrx.
   - **Property Secret**: Unique key to access the Sensfrx cognitive engine.
   - **Property Name**: Optional identifier for your property (e.g., Example.com).

### Step 2: Pre-installation Requirements
Ensure the following requirements are met:
- A web server supporting PHP and MySQL®.
- HTTPS support.
- Blesta admin login credentials.
- Recommended memory limit: 256 MB or higher.

### Step 3: Install the Sensfrx Module
1. **Download the Module**:
   - Download the Sensfrx module from the Blesta marketplace (a ZIP file).
2. **Unzip and Upload**:
   - Unzip the downloaded file.
   - Use an FTP client to upload the unzipped contents to `<blestadir>/plugins/`.
3. **Activate the Module**:
   - Log in to Blesta Admin.
   - Navigate to **System Settings** > **Addon Modules**.
   - Locate Sensfrx and click **Activate**.

---

## Configuration

### Step 4: Integrate Sensfrx with Blesta
1. **Access the Plugin**:
   - Go to **System Settings** > **Plugins** > **Installed**.
   - Locate Sensfrx and click **Manage**.
2. **Enter Configuration Details**:
   - Input the following:
     - **Domain Name**
     - **Property ID**
     - **Property Secret**
   - Click **Save** to apply changes.
3. **Set Policies**:
   - Navigate to **Addons** > **Sensfrx** in the Nav Bar.
   - Configure Sensfrx policies as required.
4. **Authorize Webhook Communication**:
   - Select the **Sensfrx** option in the menu.
   - Click on the **Webhook Update** tab to authorize communication between your Blesta application and Sensfrx.

---

## Dashboard

Once integrated, access detailed fraud analytics and real-time risk scores via the Sensfrx dashboard.

[Learn more about using the Sensfrx dashboard](#).

---

## Support
For assistance, visit the [Sensfrx Documentation](https://docs.sensfrx.ai) or contact our support team.

---

## License
This extension is licensed under the MIT License. See the LICENSE file for details.
