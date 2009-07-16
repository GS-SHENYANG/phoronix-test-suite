<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	phodevi_system.php: The PTS Device Interface object for the system software

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class phodevi_system extends pts_device_interface
{
	public static function read_sensor($identifier)
	{
		switch($identifier)
		{
			case "temperature":
				$sensor = "sys_temperature";
				break;
			case "cpu-voltage":
				$sensor = "sys_cpu_voltage";
				break;
			case "v3-voltage":
				$sensor = "sys_v3_voltage";
				break;
			case "v5-voltage":
				$sensor = "sys_v5_voltage";
				break;
			case "v12-voltage":
				$sensor = "sys_v12_voltage";
				break;
			case "power-consumption":
				$sensor = "sys_power_consumption_rate";
				break;
			default:
				$sensor = false;
				break;
		}

		return $sensor;
	}
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "username":
				$property = new pts_device_property("sw_username", PHODEVI_STAND_CACHE);
				break;
			case "hostname":
				$property = new pts_device_property("sw_hostname", PHODEVI_STAND_CACHE);
				break;
			case "vendor-identifier":
				$property = new pts_device_property("sw_vendor_identifier", PHODEVI_SMART_CACHE);
				break;
			case "filesystem":
				$property = new pts_device_property("sw_filesystem", PHODEVI_SMART_CACHE);
				break;
			case "virtualized-mode":
				$property = new pts_device_property("sw_virtualized_mode", PHODEVI_SMART_CACHE);
				break;
			case "java-version":
				$property = new pts_device_property("sw_java_version", PHODEVI_STAND_CACHE);
				break;
			case "display-server":
				$property = new pts_device_property("sw_display_server", PHODEVI_STAND_CACHE);
				break;
			case "display-driver":
				$property = new pts_device_property("sw_display_driver", PHODEVI_STAND_CACHE);
				break;
			case "dri-display-driver":
				$property = new pts_device_property("sw_dri_display_driver", PHODEVI_STAND_CACHE);
				break;
			case "opengl-driver":
				$property = new pts_device_property("sw_opengl_driver", PHODEVI_STAND_CACHE);
				break;
			case "desktop-environment":
				$property = new pts_device_property("sw_desktop_environment", PHODEVI_STAND_CACHE);
				break;
			case "operating-system":
				$property = new pts_device_property("sw_operating_system", PHODEVI_SMART_CACHE);
				break;
			case "os-version":
				$property = new pts_device_property("sw_os_version", PHODEVI_SMART_CACHE);
				break;
			case "os-vendor":
				$property = new pts_device_property("sw_os_vendor", PHODEVI_SMART_CACHE);
				break;
			case "kernel":
				$property = new pts_device_property("sw_kernel", PHODEVI_SMART_CACHE);
				break;
			case "kernel-architecture":
				$property = new pts_device_property("sw_kernel_architecture", PHODEVI_SMART_CACHE);
				break;
			case "compiler":
				$property = new pts_device_property("sw_compiler", PHODEVI_STAND_CACHE);
				break;
		}

		return $property;
	}
	public static function sys_cpu_voltage()
	{
		$voltage = read_sensors("VCore");

		return ($voltage == null ? -1 : $voltage);
	}
	public static function sys_v3_voltage()
	{
		$voltage = read_sensors(array("V3.3", "+3.3V"));

		return ($voltage == null ? -1 : $voltage);
	}
	public static function sys_v5_voltage()
	{
		$voltage = read_sensors(array("V5", "+5V"));

		return ($voltage == null ? -1 : $voltage);
	}
	public static function sys_v12_voltage()
	{
		$voltage = read_sensors(array("V12", "+12V"));

		return ($voltage == null ? -1 : $voltage);
	}
	public static function sys_temperature()
	{
		// Reads the system's temperature
		$temp_c = -1;

		if(IS_LINUX)
		{
			$sensors = read_sensors(array("Sys Temp", "Board Temp"));

			if(!$sensors != false && is_numeric($sensors))
			{
				$temp_c = $sensors;
			}
			else
			{
				$acpi = read_acpi(array(
					"/thermal_zone/THM1/temperature",
					"/thermal_zone/TZ00/temperature",
					"/thermal_zone/TZ01/temperature"), "temperature");

				if(($end = strpos($acpi, ' ')) > 0)
				{
					$temp_c = substr($acpi, 0, $end);
				}
			}
		}
		else if(IS_BSD)
		{
			$acpi = read_sysctl("hw.acpi.thermal.tz1.temperature");

			if(($end = strpos($acpi, 'C')) > 0)
			{
				$acpi = substr($acpi, 0, $end);

				if(is_numeric($acpi))
				{
					$temp_c = $acpi;
				}
			}
		}

		return $temp_c;
	}
	function sys_power_consumption_rate()
	{
		// Returns power consumption rate in mW
		$battery = array("/battery/BAT0/state", "/battery/BAT1/state");
		$state = read_acpi($battery, "charging state");
		$power = read_acpi($battery, "present rate");
		$voltage = read_acpi($battery, "present voltage");
		$rate = -1;

		if($state == "discharging")
		{
			$power_unit = substr($power, strrpos($power, " ") + 1);
			$power = substr($power, 0, strpos($power, " "));

			if($power_unit == "mA")
			{
				$voltage_unit = substr($voltage, strrpos($voltage, " ") + 1);
				$voltage = substr($voltage, 0, strpos($voltage, " "));

				if($voltage_unit == "mV")
				{
					$rate = round(($power * $voltage) / 1000);
				}				
			}
			else if($power_unit == "mW")
			{
				$rate = $power;
			}
		}

		return $rate;
	}
	public static function sw_username()
	{
		// Gets the system user's name
		if(function_exists("posix_getpwuid") && function_exists("posix_getuid"))
		{
			$userinfo = posix_getpwuid(posix_getuid());
			$username = $userinfo["name"];
		}
		else
		{
			$username = trim(getenv("USERNAME"));
		}

		return $username;
	}
	public static function sw_hostname()
	{
		$hostname = "Unknown";

		if(is_executable("/bin/hostname"))
		{
			$hostname = trim(shell_exec("/bin/hostname 2>&1"));
		}

		return $hostname;
	}
	public static function sw_vendor_identifier()
	{
		// Returns the vendor identifier used with the External Dependencies and other distro-specific features
		$vendor = str_replace(" ", "", phodevi::read_property("system", "os-vendor"));

		if($vendor == "Unknown")
		{
			$vendor = phodevi::read_property("system", "operating-system");

			if(($spos = strpos($vendor, " ")) > 1)
			{
				$vendor = substr($vendor, 0, $spos);
			}
		}

		return strtolower($vendor);
	}
	public static function sw_filesystem()
	{
		// Determine file-system type
		$fs = null;

		if(IS_MACOSX)
		{
			$fs = read_osx_system_profiler("SPSerialATADataType", "FileSystem");
		}
		else if(IS_BSD)
		{
			if(pts_executable_in_path("mount"))
			{
				$mount = shell_exec("mount 2>&1");
				
				if(($start = strpos($mount, "on / (")) != false)
				{
					/*
					-bash-4.0$ mount
					ROOT on / (hammer, local)
					/dev/da0s1a on /boot (ufs, local)
					/pfs/@@-1:00001 on /var (null, local)
					/pfs/@@-1:00002 on /tmp (null, local)
					/pfs/@@-1:00003 on /usr (null, local)
					/pfs/@@-1:00004 on /home (null, local)
					/pfs/@@-1:00005 on /usr/obj (null, local)
					/pfs/@@-1:00006 on /var/crash (null, local)
					/pfs/@@-1:00007 on /var/tmp (null, local)
					procfs on /proc (procfs, local)
					*/

					// TODO: improve this in case there are other partitions, etc
					$fs = substr($mount, $start + 6);
					$fs = substr($fs, 0, strpos($fs, ","));
				}
			}
		}
		else
		{
			$fs = trim(shell_exec("stat " . TEST_ENV_DIR . " -L -f -c %T 2> /dev/null"));

			switch($fs)
			{
				case "UNKNOWN (0x9123683e)":
					$fs = "Btrfs";
					break;
				case "UNKNOWN (0x3434)":
					$fs = "NILFS2";
					break;
				case "ext2/ext3":
					if(is_readable("/proc/mounts"))
					{
						$fstab = file_get_contents("/proc/mounts");
						$fstab = str_replace("/boot ", "IGNORE", $fstab);

						$using_ext2 = strpos($fstab, " ext2") !== false;
						$using_ext3 = strpos($fstab, " ext3") !== false;
						$using_ext4 = strpos($fstab, " ext4") !== false;

						if(!$using_ext2 && !$using_ext3 && $using_ext4)
						{
							$fs = "ext4";
						}
						else if(!$using_ext2 && !$using_ext4 && $using_ext3)
						{
							$fs = "ext3";
						}
						else if(!$using_ext3 && !$using_ext4 && $using_ext2)
						{
							$fs = "ext2";
						}
					}
					break;
			}

			if(strpos($fs, "UNKNOWN") !== false && is_readable("/proc/mounts"))
			{
				$mounts = file_get_contents("/proc/mounts");
				$fs_r = array();

				if(strpos($mounts, "squashfs") != false)
				{
					array_push($fs_r, "SquashFS");
				}

				if(strpos($mounts, "aufs") != false)
				{
					array_push($fs_r, "AuFS");
				}
				else if(strpos($mounts, "unionfs") != false)
				{
					array_push($fs_r, "UnionFS");
				}

				if(count($fs_r) > 0)
				{
					$fs = implode(" + ", $fs_r);
				}
			}
		}

		if(empty($fs))
		{
			$fs = "Unknown";
		}

		return $fs;
	}
	public static function sw_virtualized_mode()
	{
		// Reports if system is running virtualized
		$virtualized = null;
		$gpu = phodevi::read_name("gpu");

		if(strpos(phodevi::read_property("cpu", "model"), "QEMU") !== false)
		{
			$virtualized = "QEMU";
		}
		else if(strpos($gpu, "VMware") !== false)
		{
			$virtualized = "VMware";
		}
		else if(strpos($gpu, "VirtualBox") !== false || strpos(phodevi::read_name("motherboard"), "VirtualBox") !== false)
		{
			$virtualized = "VirtualBox";
		}

		if($virtualized != null)
		{
			$virtualized = "This system was using " . $virtualized . " virtualization";
		}

		return $virtualized;
	}
	public static function sw_compiler()
	{
		// Returns version of the compiler (if present)
		$info = trim(shell_exec("cc -dumpversion 2>&1"));
		$compiler_info = null;

		if(strlen(pts_remove_chars($info, false, false, true, true)) == 0)
		{
			// GCC
			$gcc_info = trim(shell_exec("gcc -dumpversion 2>&1"));

			if($gcc_info == $info)
			{
				$compiler_info = "GCC " . $info;
			}
		}
		else if(IS_SOLARIS)
		{
			// Sun Studio / SunCC
			$info = trim(shell_exec("suncc -V 2>&1"));

			if(($s = strpos($info, "Sun C")) != false)
			{
				$info = substr($info, $s);
				$info = substr($info, 0, strpos($info, "\n"));

				$compiler_info = $info;
			}
		}

		if($compiler_info == null)
		{
			// LLVM - Low Level Virtual Machine (llvmc)
			$info = trim(shell_exec("llvmc -version 2>&1"));

			if(($s = strpos($info, "version")) != false)
			{
				$info = substr($info, 0, strpos($info, "\n", $s));
				$info = substr($info, strrpos($info, "\n"));

				$compiler_info = trim($info);
			}
		}

		if($compiler_info == null)
		{
			$compiler_info = "N/A";
		}

		return $compiler_info;
	}
	public static function sw_kernel()
	{
		return trim(shell_exec("uname -r 2>&1"));
	}
	public static function sw_kernel_architecture()
	{
		// Find out the kernel archiecture
		$kernel_arch = trim(shell_exec("uname -m 2>&1"));

		switch($kernel_arch)
		{
			case "X86-64":
				$kernel_arch = "x86_64";
				break;
			case "i86pc":
				$kernel_arch = "i686";
				break;
		}

		return $kernel_arch;
	}
	public static function sw_os_version()
	{
		// Returns OS version
		if(IS_MACOSX)
		{
			$os = read_osx_system_profiler("SPSoftwareDataType", "SystemVersion");
		
			$start_pos = strpos($os, ".");
			$end_pos = strrpos($os, ".");
			$start_pos = strrpos(substr($os, 0, $start_pos), " ");
			$end_pos = strpos($os, " ", $end_pos);
		
			$os_version = substr($os, $start_pos + 1, $end_pos - $start_pos);
		}
		else if(IS_LINUX)
		{
			$os_version = read_lsb("Release");
		}
		else
		{
			$os_version = null;
		}

		if(empty($os_version))
		{
			$os_version = "Unknown";
		}	
	
		return $os_version;
	}
	public static function sw_os_vendor()
	{
		// Returns OS vendor
		$vendor = IS_LINUX ? read_lsb("Distributor ID") : false;

		if($vendor == false)
		{
			$vendor = "Unknown";
		}

		return $vendor;
	}
	public static function sw_operating_system()
	{
		// Determine the operating system release
		$vendor = phodevi::read_property("system", "os-vendor");
		$version = phodevi::read_property("system", "os-version");

		if($vendor == "Unknown" && $version == "Unknown")
		{
			$os = null;

			// Try to detect distro for those not supplying lsb_release
			$files = glob("/etc/*-version");
			for($i = 0; $i < count($files) && $os == null; $i++)
			{
				$file = file_get_contents($files[$i]);

				if(trim($file) != "")
				{
					$os = substr($file, 0, strpos($file, "\n"));
				}
			}
		
			if($os == null)
			{
				$files = glob("/etc/*-release");
				for($i = 0; $i < count($files) && $os == null; $i++)
				{
					$file = file_get_contents($files[$i]);

					if(trim($file) != "")
					{
						$proposed_os = substr($file, 0, strpos($file, "\n"));

						if(strpos($proposed_os, "=") == false)
						{
							$os = $proposed_os;
						}
					}
					else if($i == (count($files) - 1))
					{
						$os = ucwords(substr(($n = basename($files[$i])), 0, strpos($n, "-")));
					}			
				}

				if($os == null)
				{
					if(is_file("/etc/release"))
					{
						$file = file_get_contents("/etc/release");
						$os = substr($file, 0, strpos($file, "\n"));
					}
				}
			}

			if($os == null)
			{
				$os = shell_exec("uname -s 2>&1");
			}
		}
		else
		{
			$os = $vendor . " " . $version;
		}

		if(($break_point = strpos($os, ":")) > 0)
		{
			$os = substr($os, $break_point + 1);
		}
		
		if(IS_MACOSX)
		{
			$os = read_osx_system_profiler("SPSoftwareDataType", "SystemVersion");
		
			if(($cut_point = strpos($os, "(")) > 0)
			{
				$os = substr($os, 0, $cut_point);
			}
		}

		$os = trim($os);

		return $os;
	}
	public static function sw_desktop_environment()
	{
		$desktop = null;
		$desktop_environment = null;
		$desktop_version = null;

		if(pts_process_running_bool("gnome-panel"))
		{
			// GNOME
			$desktop_environment = "GNOME";

			if(pts_executable_in_path("gnome-about") != false)
			{
				$desktop_version = array_pop(explode(" ", trim(shell_exec("gnome-about --version 2>&1"))));
			}
		}
		else if(($kde4 = pts_process_running_bool("kded4")) || pts_process_running_bool("kded"))
		{
			// KDE 4.x
			$desktop_environment = "KDE";
			$kde_output = trim(shell_exec(($kde4 ? "kde4-config" : "kde-config") . " --version 2>&1"));
			$kde_lines = explode("\n", $kde_output);

			for($i = 0; $i < count($kde_lines) && empty($desktop_version); $i++)
			{
				$line_segments = explode(":", $kde_lines[$i]);

				if($line_segments[0] == "KDE" && isset($line_segments[1]))
				{
					$v = trim($line_segments[1]);

					if(($cut = strpos($v, " ")) > 0)
					{
						$v = substr($v, 0, $cut);
					}

					$desktop_version = $v;
				}
			}

		}
		else if(pts_process_running_bool("xfce4-session") || pts_process_running_bool("xfce-mcs-manager"))
		{
			// Xfce 4.x
			$desktop_environment = "Xfce";
			$xfce_output = trim(shell_exec("xfce4-session-settings --version 2>&1"));

			if(($open = strpos($xfce_output, "(")) > 0)
			{
				$xfce_output = substr($xfce_output, strpos($xfce_output, " ", $open) + 1);
				$desktop_version = substr($xfce_output, 0, strpos($xfce_output, ")"));
			}
		}

		if(!empty($desktop_environment))
		{
			$desktop = $desktop_environment;
			$version_check = str_replace(array(".", 1, 2, 3, 4, 5, 6, 7, 8, 9, 0), "", $desktop_version);

			if(!empty($desktop_version) && empty($version_check))
			{
				$desktop .= " " . $desktop_version;
			}
		}

		return $desktop;
	}
	public static function sw_display_server()
	{
		// Find graphics subsystem version
		$info = shell_exec("X " . (IS_SOLARIS ? ":0" : "") . " -version 2>&1");
		$pos = (($p = strrpos($info, "Release Date")) !== false ? $p : strrpos($info, "Build Date"));	
		$info = trim(substr($info, 0, $pos));

		if($pos === false)
		{
			$info = "Unknown";
		}
		else if(($pos = strrpos($info, "(")) === false)
		{
			$info = trim(substr($info, strrpos($info, " ")));
		}
		else
		{
			$info = trim(substr($info, strrpos($info, "Server") + 6));
		}

		if($info != "Unknown")
		{
			$info = "X.Org Server " . $info;
		}

		return $info;
	}
	public static function sw_display_driver()
	{
		$ddx_info = "";
		$dri_driver = phodevi::read_property("system", "dri-display-driver");

		if(!empty($dri_driver))
		{
			$driver_version = read_xorg_module_version($dri_driver . "_drv");

			if(!empty($driver_version))
			{
				$ddx_info = $dri_driver . " " . $driver_version;
			}
		}
		else if(IS_MESA_GRAPHICS && stripos(phodevi::read_name("gpu"), "NVIDIA") !== false)
		{
			// xf86-video-nv is an open-source driver but currently doesn't support DRI
			$nv_driver_version = read_xorg_module_version("nv_drv.so");

			if(!empty($nv_driver_version))
			{
				$ddx_info = "nv " . $nv_driver_version;
			}
		}

		return $ddx_info;
	}
	public static function sw_opengl_driver()
	{
		// OpenGL version
		$info = pts_executable_in_path("glxinfo") != false ? shell_exec("glxinfo 2>&1 | grep version") : null;

		if(($pos = strpos($info, "OpenGL version string:")) === false)
		{
			$info = "N/A";
		}
		else
		{
			$info = substr($info, $pos + 23);
			$info = trim(substr($info, 0, strpos($info, "\n")));
			$info = str_replace(array(" Release"), "", $info);
		}

		return $info;
	}
	public static function sw_dri_display_driver()
	{
		$dri_driver = false;

		if(is_file("/proc/dri/0/name"))
		{
			$driver_info = file_get_contents("/proc/dri/0/name");
			$dri_driver = substr($driver_info, 0, strpos($driver_info, " "));

			if($dri_driver == "i915")
			{
				$dri_driver = "intel";
			}
		}

		return $dri_driver;
	}
	public static function sw_java_version()
	{
		$java_version = trim(shell_exec("java -version 2>&1"));

		if(strpos($java_version, "not found") == false && strpos($java_version, "Java") !== FALSE)
		{
			$java_version = explode("\n", $java_version);

			if(($cut = count($java_version) - 2) > 0)
			{
				$v = $java_version[$cut];
			}
			else
			{
				$v = array_pop($java_version);
			}

			$java_version = trim($v);
		}
		else
		{
			$java_version = "";
		}

		return $java_version;
	}
}

?>
