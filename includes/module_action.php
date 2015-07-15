<? 
/*
    Copyright (C) 2013-2015 xtr4nge [_AT_] gmail.com

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/ 
?>
<?
include "../../../login_check.php";
include "../../../config/config.php";
include "../_info_.php";
include "../../../functions.php";

// Checking POST & GET variables...
if ($regex == 1) {
    regex_standard($_GET["service"], "../msg.php", $regex_extra);
    regex_standard($_GET["action"], "../msg.php", $regex_extra);
    regex_standard($_GET["page"], "../msg.php", $regex_extra);
    regex_standard($_GET["install"], "../msg.php", $regex_extra);
}

$service = $_GET['service'];
$action = $_GET['action'];
$page = $_GET['page'];
$install = $_GET['install'];

function flushIptables() {	
	global $bin_iptables;
	
	$exec = "$bin_iptables -F";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t nat -F";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t mangle -F";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -X";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t nat -X";
	exec_fruitywifi($exec);
	$exec = "$bin_iptables -t mangle -X";
	exec_fruitywifi($exec);
	echo $exec;
}

function copyLogsHistory() {
	
	global $bin_cp;
	global $bin_mv;
	global $mod_logs;
	global $mod_logs_history;
	global $bin_echo;
	
	if ( 0 < filesize( $mod_logs ) ) {
		$exec = "$bin_cp $mod_logs $mod_logs_history/".gmdate("Ymd-H-i-s").".log";
		exec_fruitywifi($exec);
		
		$exec = "$bin_echo '' > $mod_logs";
		exec_fruitywifi($exec);
	}
}

// HOSTAPD
if($service != "" and $ap_mode == "1") {
	if ($action == "start") {
		
		$exec = "$bin_ifconfig $io_in_iface down";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $io_in_iface 0.0.0.0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_killall hostapd";	
		exec_fruitywifi($exec);

		$exec = "$bin_rm /var/run/hostapd-phy0/$io_in_iface";
		exec_fruitywifi($exec);

		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);

		$exec = "$bin_ifconfig $io_in_iface up";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $io_in_iface up $io_in_ip netmask 255.255.255.0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_echo 'nameserver $io_in_ip\nnameserver 8.8.8.8' > /etc/resolv.conf ";
		exec_fruitywifi($exec);
		
		//$exec = "/etc/init.d/dnsmasq restart";
		$exec = "$bin_dnsmasq -C /usr/share/fruitywifi/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
	
		//Verifies if karma-hostapd is installed
		if ($hostapd_secure == 1) {
			
			//REPLACE SSID
			$exec = "$bin_sed -i 's/^ssid=.*/ssid=".$hostapd_ssid."/g' /usr/share/fruitywifi/conf/hostapd-secure.conf";
			exec_fruitywifi($exec);
			
			//REPLACE IFACE                
			$exec = "$bin_sed -i 's/^interface=.*/interface=".$io_in_iface."/g' /usr/share/fruitywifi/conf/hostapd-secure.conf";
			exec_fruitywifi($exec);
			
			//REPLACE WPA_PASSPHRASE
			$exec = "$bin_sed -i 's/wpa_passphrase=.*/wpa_passphrase=".$hostapd_wpa_passphrase."/g' /usr/share/fruitywifi/conf/hostapd-secure.conf";
			exec_fruitywifi($exec);
			
			//EXTRACT MACADDRESS
			unset($output);
			$exec = "$bin_ifconfig -a $io_in_iface |grep HWaddr";
			$output = exec_fruitywifi($exec);
			$output = preg_replace('/\s+/', ' ',$output[0]);
			$output = explode(" ",$output);
			
			//REPLACE MAC
			$exec = "$bin_sed -i 's/^bssid=.*/bssid=".$output[4]."/g' /usr/share/fruitywifi/conf/hostapd-secure.conf";
			exec_fruitywifi($exec);
			
			$exec = "/usr/sbin/hostapd -P /var/run/hostapd-phy0 -B /usr/share/fruitywifi/conf/hostapd-secure.conf";
		} else {
			
			//REPLACE SSID
			$exec = "$bin_sed -i 's/^ssid=.*/ssid=".$hostapd_ssid."/g' /usr/share/fruitywifi/conf/hostapd.conf";
			exec_fruitywifi($exec);
			
			//REPLACE IFACE                
			$exec = "$bin_sed -i 's/^interface=.*/interface=".$io_in_iface."/g' /usr/share/fruitywifi/conf/hostapd.conf";
			exec_fruitywifi($exec);
			
			//REPLACE WPA_PASSPHRASE
			$exec = "$bin_sed -i 's/wpa_passphrase=.*/wpa_passphrase=".$hostapd_wpa_passphrase."/g' /usr/share/fruitywifi/conf/hostapd.conf";
			exec_fruitywifi($exec);
			
			//EXTRACT MACADDRESS
			unset($output);
			$exec = "$bin_ifconfig -a $io_in_iface |grep HWaddr";
			$output = exec_fruitywifi($exec);
			$output = preg_replace('/\s+/', ' ',$output[0]);
			$output = explode(" ",$output);
			
			//REPLACE BSSID
			$exec = "$bin_sed -i 's/^bssid=.*/bssid=".$output[4]."/g' /usr/share/fruitywifi/conf/hostapd.conf";
			exec_fruitywifi($exec);
			
			$exec = "/usr/sbin/hostapd -P /var/run/hostapd-phy0 -B /usr/share/fruitywifi/conf/hostapd.conf";
		}
		exec_fruitywifi($exec);

		// IPTABLES	FLUSH	
		flushIptables();
		
		$exec = "$bin_echo 1 > /proc/sys/net/ipv4/ip_forward";
		exec_fruitywifi($exec);
		$exec = "$bin_iptables -t nat -A POSTROUTING -o $io_out_iface -j MASQUERADE";
		exec_fruitywifi($exec);
		
		// CLEAN DHCP log
		$exec = "$bin_echo '' > /usr/share/fruitywifi/logs/dhcp.leases";
		exec_fruitywifi($exec);

	} else if($action == "stop") {

		if (file_exists("/usr/share/fruitywifi/www/modules/karma/includes/hostapd")) {
			$exec = "$bin_killall hostapd";
		} else {
			$exec = "$bin_killall hostapd";			
		}	
		exec_fruitywifi($exec);

		$exec = "$bin_rm /var/run/hostapd-phy0/$io_in_iface";
		exec_fruitywifi($exec);

		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);

		$exec = "ip addr flush dev $io_in_iface";
		exec_fruitywifi($exec);
		
		$exec = "$bin_ifconfig $io_in_iface down";
		exec_fruitywifi($exec);
		
		// IPTABLES	FLUSH	
		flushIptables();
		
		// LOGS COPY
		copyLogsHistory();
		
	}
}

// AIRCRACK
if($service != "" and $ap_mode == "2") { // AIRCRACK (airbase-ng)
	if ($action == "start") {

		$exec = "/usr/bin/sudo /usr/sbin/airmon-ng stop mon0";
		exec_fruitywifi($exec);
	
		$exec = "$bin_killall airbase-ng";
		exec_fruitywifi($exec);
	
		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);
			
		$exec = "$bin_echo 'nameserver $io_in_ip\nnameserver 8.8.8.8' > /etc/resolv.conf ";
		exec_fruitywifi($exec);
			
		$exec = "/usr/bin/sudo /usr/sbin/airmon-ng start $io_in_iface";
		exec_fruitywifi($exec);
		
		//$exec = "/usr/sbin/airbase-ng -e $hostapd_ssid -c 2 mon0 > /dev/null &"; //-P (all)
		$exec = "/usr/sbin/airbase-ng -e $hostapd_ssid -c 2 mon0 > /tmp/airbase.log &"; //-P (all)
		exec_fruitywifi($exec);

		//$exec = "$bin_ifconfig at0 up 10.0.0.1 netmask 255.255.255.0";
		//exec("$bin_danger \"" . $exec . "\"" ); //DEPRECATED

		$exec = "sleep 1";
		exec_fruitywifi($exec);

		$exec = "$bin_ifconfig at0 up";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig at0 up $io_in_ip netmask 255.255.255.0";
		exec_fruitywifi($exec);

		$exec = "$bin_dnsmasq -C /usr/share/fruitywifi/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
		
		// IPTABLES	FLUSH	
		flushIptables();
		
		$exec = "$bin_echo 1 > /proc/sys/net/ipv4/ip_forward";
		exec_fruitywifi($exec);
		$exec = "$bin_iptables -t nat -A POSTROUTING -o $io_out_iface -j MASQUERADE";
		exec_fruitywifi($exec);
		
		// CLEAN DHCP log
		$exec = "$bin_echo '' > /usr/share/fruitywifi/logs/dhcp.leases";
		exec_fruitywifi($exec);

	} else if($action == "stop") {

		$exec = "$bin_killall airbase-ng";
		exec_fruitywifi($exec);

		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);

		$exec = "/usr/bin/sudo /usr/sbin/airmon-ng stop mon0";
		exec_fruitywifi($exec);

		$exec = "ip addr flush dev at0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_ifconfig at0 down";
		exec_fruitywifi($exec);

		// IPTABLES	FLUSH	
		flushIptables();
		
		// LOGS COPY
		copyLogsHistory();
		
	}
}

// HOSTAPD MANA
if($service != ""  and $ap_mode == "3") {
	if ($action == "start") {
		
		//unmanaged-devices=mac:<realmac>;interface-name:wlan2
		//macchanger --show wlan0 |grep "Permanent"
		
		$exec = "macchanger --show $io_in_iface |grep 'Permanent'";
		exec($exec, $output);
		$mac = explode(" ", $output[0]);
		
		$exec = "grep '^unmanaged-devices' /etc/NetworkManager/NetworkManager.conf";
		$ispresent = exec($exec);
		
		$exec = "$bin_sed -i '/unmanaged/d' /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
		$exec = "$bin_sed -i '/\[keyfile\]/d' /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
		
		if ($ispresent == "") {
			$exec = "$bin_echo '[keyfile]' >> /etc/NetworkManager/NetworkManager.conf";
			exec_fruitywifi($exec);

			$exec = "$bin_echo 'unmanaged-devices=mac:".$mac[2].";interface-name:".$io_in_iface."' >> /etc/NetworkManager/NetworkManager.conf";
			exec_fruitywifi($exec);
		}
		
		$exec = "$bin_killall hostapd";
		exec_fruitywifi($exec);

		$exec = "$bin_rm /var/run/hostapd-phy0/$io_in_iface";
		exec_fruitywifi($exec);

		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);

		$exec = "$bin_ifconfig $io_in_iface up";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $io_in_iface up $io_in_ip netmask 255.255.255.0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_echo 'nameserver $io_in_ip\nnameserver 8.8.8.8' > /etc/resolv.conf ";
		exec_fruitywifi($exec);
		
		$exec = "$bin_dnsmasq -C /usr/share/fruitywifi/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
	
		//Verifies if mana-hostapd is installed
		if ($hostapd_secure == 1) {
			
			if (file_exists("/usr/share/fruitywifi/www/modules/mana/includes/hostapd")) {
				include "/usr/share/fruitywifi/www/modules/mana/_info_.php";
				
				//REPLACE SSID
				$exec = "$bin_sed -i 's/^ssid=.*/ssid=".$hostapd_ssid."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				//REPLACE IFACE                
				$exec = "$bin_sed -i 's/^interface=.*/interface=".$io_in_iface."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				//REPLACE WPA_PASSPHRASE
				$exec = "$bin_sed -i 's/wpa_passphrase=.*/wpa_passphrase=".$hostapd_wpa_passphrase."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				//EXTRACT MACADDRESS
				unset($output);
				$exec = "$bin_ifconfig -a $io_in_iface |grep HWaddr";
				$output = exec_fruitywifi($exec);
				$output = preg_replace('/\s+/', ' ',$output[0]);
				$output = explode(" ",$output);
				
				//REPLACE MAC
				$exec = "$bin_sed -i 's/^bssid=.*/bssid=".$output[4]."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				$exec = "$bin_hostapd $mod_path/includes/conf/hostapd-secure.conf >> $mod_logs &";
			} else {
				$exec = "/usr/sbin/hostapd -P /var/run/hostapd-phy0 -B /usr/share/fruitywifi/conf/hostapd-secure.conf";
			}
			
		} else {
			
			if (file_exists("/usr/share/fruitywifi/www/modules/mana/includes/hostapd")) {
				include "/usr/share/fruitywifi/www/modules/mana/_info_.php";
				
				//REPLACE SSID
				$exec = "$bin_sed -i 's/^ssid=.*/ssid=".$hostapd_ssid."/g' $mod_path/includes/conf/hostapd.conf";
				exec_fruitywifi($exec);
				
				//REPLACE IFACE                
				$exec = "$bin_sed -i 's/^interface=.*/interface=".$io_in_iface."/g' $mod_path/includes/conf/hostapd.conf";
				exec_fruitywifi($exec);
				
				//EXTRACT MACADDRESS
				unset($output);
				$exec = "$bin_ifconfig -a $io_in_iface |grep HWaddr";
				$output = exec_fruitywifi($exec);
				$output = preg_replace('/\s+/', ' ',$output[0]);
				$output = explode(" ",$output);
				
				//REPLACE MAC
				$exec = "$bin_sed -i 's/^bssid=.*/bssid=".$output[4]."/g' $mod_path/includes/conf/hostapd.conf";
				exec_fruitywifi($exec);
				
				$exec = "$bin_hostapd $mod_path/includes/conf/hostapd.conf >> $mod_logs &";
			} else {
				$exec = "/usr/sbin/hostapd -P /var/run/hostapd-phy0 -B /usr/share/fruitywifi/conf/hostapd.conf";
			}
			
		}
		exec_fruitywifi($exec);

		// IPTABLES	FLUSH	
		flushIptables();
		
		$exec = "$bin_echo 1 > /proc/sys/net/ipv4/ip_forward";
		exec_fruitywifi($exec);
		$exec = "$bin_iptables -t nat -A POSTROUTING -o $io_out_iface -j MASQUERADE";
		exec_fruitywifi($exec);
		
		// CLEAN DHCP log
		$exec = "$bin_echo '' > /usr/share/fruitywifi/logs/dhcp.leases";
		exec_fruitywifi($exec);

	} else if($action == "stop") {

		// REMOVE lines from NetworkManager
		$exec = "$bin_sed -i '/unmanaged/d' /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
		$exec = "$bin_sed -i '/\[keyfile\]/d' /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
	
		$exec = "$bin_killall hostapd";	
		exec_fruitywifi($exec);

		$exec = "$bin_rm /var/run/hostapd-phy0/$io_in_iface";
		exec_fruitywifi($exec);

		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);

		$exec = "ip addr flush dev $io_in_iface";
		exec_fruitywifi($exec);
		
		$exec = "$bin_ifconfig $io_in_iface down";
		exec_fruitywifi($exec);

		// IPTABLES	FLUSH	
		flushIptables();
		
		// LOGS COPY
		copyLogsHistory();
		
	}
}

// HOSTAPD KARMA
if($service != ""  and $ap_mode == "4") {
	if ($action == "start") {
		
		//unmanaged-devices=mac:<realmac>;interface-name:wlan2
		//macchanger --show wlan0 |grep "Permanent"
		
		$exec = "macchanger --show eth0 |grep 'Permanent'";
		//$output = exec_fruitywifi($exec);
		exec($exec, $output);
		$mac = explode(" ", $output[0]);
		
		$exec = "grep '^unmanaged-devices' /etc/NetworkManager/NetworkManager.conf";
		$ispresent = exec($exec);
		
		$exec = "$bin_sed -i '/unmanaged/d' /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
		$exec = "$bin_sed -i '/[keyfile]/d' /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
		
		if ($ispresent == "") {
			$exec = "$bin_echo '[keyfile]' >> /etc/NetworkManager/NetworkManager.conf";
			exec_fruitywifi($exec);

			$exec = "$bin_echo 'unmanaged-devices=mac:".$mac[2].";interface-name:".$io_in_iface."' >> /etc/NetworkManager/NetworkManager.conf";
			exec_fruitywifi($exec);
		}
		
		$exec = "$bin_killall hostapd";
		exec_fruitywifi($exec);

		$exec = "$bin_rm /var/run/hostapd-phy0/$io_in_iface";
		exec_fruitywifi($exec);

		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);

		$exec = "$bin_ifconfig $io_in_iface up";
		exec_fruitywifi($exec);
		$exec = "$bin_ifconfig $io_in_iface up $io_in_ip netmask 255.255.255.0";
		exec_fruitywifi($exec);
		
		$exec = "$bin_echo 'nameserver $io_in_ip\nnameserver 8.8.8.8' > /etc/resolv.conf ";
		exec_fruitywifi($exec);
		
		$exec = "$bin_dnsmasq -C /usr/share/fruitywifi/conf/dnsmasq.conf";
		exec_fruitywifi($exec);
	
		//Verifies if karma-hostapd is installed
		if ($hostapd_secure == 1) {
			
			if (file_exists("/usr/share/fruitywifi/www/modules/karma/includes/hostapd")) {
				include "/usr/share/fruitywifi/www/modules/karma/_info_.php";
				
				//REPLACE SSID
				$exec = "$bin_sed -i 's/^ssid=.*/ssid=".$hostapd_ssid."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				//REPLACE IFACE                
				$exec = "$bin_sed -i 's/^interface=.*/interface=".$io_in_iface."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				//REPLACE WPA_PASSPHRASE
				$exec = "$bin_sed -i 's/wpa_passphrase=.*/wpa_passphrase=".$hostapd_wpa_passphrase."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				//EXTRACT MACADDRESS
				unset($output);
				$exec = "$bin_ifconfig -a $io_in_iface |grep HWaddr";
				$output = exec_fruitywifi($exec);
				$output = preg_replace('/\s+/', ' ',$output[0]);
				$output = explode(" ",$output);
				
				//REPLACE MAC
				$exec = "$bin_sed -i 's/^bssid=.*/bssid=".$output[4]."/g' $mod_path/includes/conf/hostapd-secure.conf";
				exec_fruitywifi($exec);
				
				$exec = "$bin_hostapd $mod_path/includes/conf/hostapd-secure.conf >> $mod_logs &";
			} else {
				$exec = "/usr/sbin/hostapd -P /var/run/hostapd-phy0 -B /usr/share/fruitywifi/conf/hostapd-secure.conf";
			}
			
		} else {
			
			if (file_exists("/usr/share/fruitywifi/www/modules/karma/includes/hostapd")) {
				include "/usr/share/fruitywifi/www/modules/karma/_info_.php";
				
				//REPLACE SSID
				$exec = "$bin_sed -i 's/^ssid=.*/ssid=".$hostapd_ssid."/g' $mod_path/includes/conf/hostapd.conf";
				exec_fruitywifi($exec);
				
				//REPLACE IFACE                
				$exec = "$bin_sed -i 's/^interface=.*/interface=".$io_in_iface."/g' $mod_path/includes/conf/hostapd.conf";
				exec_fruitywifi($exec);
				
				//EXTRACT MACADDRESS
				unset($output);
				$exec = "$bin_ifconfig -a $io_in_iface |grep HWaddr";
				$output = exec_fruitywifi($exec);
				$output = preg_replace('/\s+/', ' ',$output[0]);
				$output = explode(" ",$output);
				
				//REPLACE MAC
				$exec = "$bin_sed -i 's/^bssid=.*/bssid=".$output[4]."/g' $mod_path/includes/conf/hostapd.conf";
				exec_fruitywifi($exec);
				
				$exec = "$bin_hostapd $mod_path/includes/conf/hostapd.conf >> $mod_logs &";
			} else {
				$exec = "/usr/sbin/hostapd -P /var/run/hostapd-phy0 -B /usr/share/fruitywifi/conf/hostapd.conf";
			}
			
		}
		exec_fruitywifi($exec);

		// IPTABLES	FLUSH	
		flushIptables();
		
		$exec = "$bin_echo 1 > /proc/sys/net/ipv4/ip_forward";
		exec_fruitywifi($exec);
		$exec = "$bin_iptables -t nat -A POSTROUTING -o $io_out_iface -j MASQUERADE";
		exec_fruitywifi($exec);
		
		// CLEAN DHCP log
		$exec = "$bin_echo '' > /usr/share/fruitywifi/logs/dhcp.leases";
		exec_fruitywifi($exec);

	} else if($action == "stop") {

		// REMOVE lines from NetworkManager
		$exec = "$bin_sed -i '/unmanaged/d' /etc/NetworkManager/NetworkManager.conf";
		exec_fruitywifi($exec);
		$exec = "$bin_sed -i '/[keyfile]/d' /etc/NetworkMxanager/NetworkManager.conf";
		exec_fruitywifi($exec);
	
		$exec = "$bin_killall hostapd";	
		exec_fruitywifi($exec);

		$exec = "$bin_rm /var/run/hostapd-phy0/$io_in_iface";
		exec_fruitywifi($exec);

		$exec = "$bin_killall dnsmasq";
		exec_fruitywifi($exec);

		$exec = "ip addr flush dev $io_in_iface";
		exec_fruitywifi($exec);
		
		$exec = "$bin_ifconfig $io_in_iface down";
		exec_fruitywifi($exec);

		// IPTABLES	FLUSH	
		flushIptables();
		
		// LOGS COPY
		copyLogsHistory();
	}
}


if ($install == "install_$mod_name") {

    $exec = "chmod 755 install.sh";
    exec_fruitywifi($exec);

    $exec = "$bin_sudo ./install.sh > $log_path/install.txt &";
    exec_fruitywifi($exec);

    header('Location: ../../install.php?module=ap');
    exit;
}

if ($page == "status") {
    header('Location: ../../../action.php');
} else {
    header('Location: ../../action.php?page=ap');
}

?>
