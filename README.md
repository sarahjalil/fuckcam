# FUCKCAM v1.0
### Camera + Location Phishing Simulation Kit
**by JUNMO — Authorized Penetration Testing Tool**

---

## ⚠️ Legal Notice

This tool is designed **exclusively for authorized security professionals** conducting legitimate penetration tests, red team exercises, and security awareness training. 

**You MUST have explicit written permission** from the target organization before using this tool. Unauthorized use is illegal and unethical. The developer (JUNMO) assumes no liability for misuse.

---

## 🔍 Overview

**FUCKCAM by JUNMO** simulates a realistic social engineering attack that:
1. Sends a link to a target (via email, SMS, chat, etc.)
2. The link leads to a convincing "video player" page
3. The page requests **camera** and **location** permissions (browser-native prompts)
4. If granted, captures a **photo** and **GPS coordinates**
5. All data is logged server-side for the tester to review

---

## 📋 Requirements

- **Kali Linux** (recommended) or any Debian-based Linux
- PHP 7.4+
- ngrok account (free tier works) — [Sign up here](https://dashboard.ngrok.com/signup)
- Internet connection

---

## ✨ New in v1.0

| Feature | Description |
|---------|-------------|
| ✅ **Real-Time GPS** | `watchPosition()` sends location every few seconds |
| ✅ **Live Map** | Leaflet.js map with OpenStreetMap tiles — no API key needed |
| ✅ **Movement Trail** | Dashed red polyline shows the target's path |
| ✅ **Auto-Zoom** | Dashboard follows the target as they move |
| ✅ **Speed & Heading** | Captures movement speed and direction |
| ✅ **2-Second Refresh** | Dashboard auto-updates every 2 seconds |
| ✅ **Photo Gallery** | Grid view of all captured photos |
| ✅ **Mobile Responsive** | Works on phone/tablet for on-the-go monitoring |

---

## 🚀 Installation & Usage

### Step 1: Setup

```bash
# Update && upgrade all files
sudo apt update && sudo apt upgrade -y

# Copy the code then paste this
git clone https://github.com/sarahjalil/fuckcam.git

# Go to the folder
cd fuckcam

# Make the script executable
chmod +x bash.sh

# Run the launcher
./bash.sh
