import os
import subprocess
import sys

def main():
    html_path = os.path.abspath("client_guide.html")
    pdf_path = os.path.expanduser("~/Desktop/Haus_of_Lyra_Client_Guide.pdf")
    chrome_path = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
    
    if not os.path.exists(html_path):
        print("Error: client_guide.html not found.", file=sys.stderr)
        return 1
        
    if not os.path.exists(chrome_path):
        print(f"Error: Google Chrome not found at {chrome_path}", file=sys.stderr)
        return 1
        
    print(f"Compiling {html_path} to {pdf_path}...")
    
    cmd = [
        chrome_path,
        "--headless",
        "--disable-gpu",
        f"--print-to-pdf={pdf_path}",
        html_path
    ]
    
    try:
        subprocess.run(cmd, check=True)
        print("Success! PDF generated at:", pdf_path)
        return 0
    except subprocess.CalledProcessError as e:
        print("Error compiling PDF:", e, file=sys.stderr)
        return 1

if __name__ == "__main__":
    sys.exit(main())
