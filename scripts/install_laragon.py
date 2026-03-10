import os
import sys
import urllib.request
import subprocess

def main():
    installer_url = "https://github.com/leokhoa/laragon/releases/download/6.0.0/laragon-wamp.exe"
    installer_path = os.path.join(os.environ.get('TEMP', 'C:\\Temp'), "laragon-installer.exe")
    install_dir = "C:\\laragon"

    print(f"Downloading Laragon from {installer_url}...")
    try:
        urllib.request.urlretrieve(installer_url, installer_path)
        print("Download complete.")
    except Exception as e:
        print(f"Error downloading Laragon: {e}")
        sys.exit(1)

    print("Running silent installation. Please wait, this may take a few minutes...")
    print(f"Installing to: {install_dir}")
    try:
        # /VERYSILENT runs the installer without any GUI
        # /DIR changes the default install directory
        # /SUPPRESSMSGBOXES prevents popups
        # /NORESTART prevents automatic restart
        install_command = f'"{installer_path}" /VERYSILENT /SUPPRESSMSGBOXES /NORESTART /DIR="{install_dir}"'
        
        process = subprocess.Popen(install_command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = process.communicate()
        
        if process.returncode == 0:
            print(f"Laragon successfully installed to {install_dir}!")
            print("You can now start Laragon from your Start Menu or desktop shortcut.")
        else:
            print(f"Installation failed with code {process.returncode}.")
            print(f"Error details: {err.decode('utf-8', errors='ignore')}")
    except Exception as e:
        print(f"Error during installation: {e}")
    finally:
        # Cleanup installer
        if os.path.exists(installer_path):
            try:
                os.remove(installer_path)
            except:
                pass

if __name__ == "__main__":
    # Request admin privileges check
    import ctypes
    if not ctypes.windll.shell32.IsUserAnAdmin():
        print("[WARNING] This script requires Administrator privileges to install Laragon.")
        print("Please run your terminal or command prompt as Administrator and try again.")
        sys.exit(1)
        
    main()
