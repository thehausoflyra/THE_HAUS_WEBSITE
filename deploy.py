import os
import sys
import getpass
import requests

# Set default configuration
DEFAULT_WP_URL = "https://thehausoflyra.com" # Live domain
DEFAULT_WP_USER = "thehausoflyra@gmail.com"

def main():
    print("=== Haus of Lyra Website Deployment Script ===")
    
    # Get credentials from environment or prompt
    wp_url = os.environ.get("WP_URL") or input(f"WordPress Site URL [{DEFAULT_WP_URL}]: ").strip() or DEFAULT_WP_URL
    wp_user = os.environ.get("WP_USER") or input(f"WordPress Username [{DEFAULT_WP_USER}]: ").strip() or DEFAULT_WP_USER
    wp_pass = os.environ.get("WP_APP_PASS") or getpass.getpass("WordPress Application Password: ").strip()
    
    if not wp_pass:
        print("Error: Application password is required to deploy.", file=sys.stderr)
        return 1

    # Ensure URL formatting
    wp_url = wp_url.rstrip('/')
    endpoint = f"{wp_url}/wp-json/haus/v1/deploy-page"
    
    # Pages to deploy
    pages = [
        {
            "slug": "home",
            "title": "Home",
            "html_file": "index.html",
        },
        {
            "slug": "about",
            "title": "About",
            "html_file": "about.html",
        },
        {
            "slug": "wedding-photography",
            "title": "Wedding Photography",
            "html_file": "wedding-photography.html",
        },
        {
            "slug": "wedding-films",
            "title": "Wedding Films",
            "html_file": "wedding-films.html",
        },
        {
            "slug": "senior-portraits",
            "title": "Senior Portraits",
            "html_file": "senior-portraits.html",
        },
        {
            "slug": "vendors",
            "title": "My Favorite Vendors",
            "html_file": "vendors.html",
        },
        {
            "slug": "wedding-gallery",
            "title": "Wedding Gallery",
            "html_file": "wedding-gallery.html",
        },
        {
            "slug": "senior-gallery",
            "title": "Senior Gallery",
            "html_file": "senior-gallery.html",
        },
        {
            "slug": "hot-takes",
            "title": "20 Hot Takes From a Wedding Vendor",
            "html_file": "hot-takes.html",
        },
        {
            "slug": "midwest-weddings",
            "title": "Midwest Weddings Are Different",
            "html_file": "midwest-weddings.html",
        },
        {
            "slug": "blog",
            "title": "The Journal",
            "html_file": "blog.html",
        },
        {
            "slug": "videography-worth-it",
            "title": "Why Wedding Videography Is Worth It",
            "html_file": "videography-worth-it.html",
        },
        {
            "slug": "wedding-trends-2027",
            "title": "Wedding Trends Defining 2027",
            "html_file": "wedding-trends-2027.html",
        },
        {
            "slug": "des-moines-wedding-destination",
            "title": "Why Des Moines is the Ultimate Wedding Destination",
            "html_file": "des-moines-wedding-destination.html",
        }
    ]

    # Read the shared stylesheet
    css_content = ""
    if os.path.exists("style.css"):
        with open("style.css", "r", encoding="utf-8") as f:
            css_content = f.read()
    else:
        print("Warning: style.css not found. Pages will be deployed without shared CSS styles.")

    # Read the shared modal script
    js_content = ""
    if os.path.exists("honeybook-modal.js"):
        with open("honeybook-modal.js", "r", encoding="utf-8") as f:
            js_content = f.read()
    else:
        print("Warning: honeybook-modal.js not found. Pages will be deployed without shared modal script.")

    # Deploy each page
    session = requests.Session()
    session.auth = (wp_user, wp_pass)
    
    success_count = 0
    for page in pages:
        html_file = page["html_file"]
        if not os.path.exists(html_file):
            print(f"Skipping {page['title']} (File {html_file} not found).")
            continue
            
        with open(html_file, "r", encoding="utf-8") as f:
            html_content = f.read()
            print(f"  -> Read {len(html_content)} characters from {html_file}")

        import base64
        encoded_html = base64.b64encode(html_content.encode("utf-8")).decode("utf-8")
        encoded_css = base64.b64encode(css_content.encode("utf-8")).decode("utf-8") if css_content else ""
        encoded_js = base64.b64encode(js_content.encode("utf-8")).decode("utf-8") if js_content else ""

        payload = {
            "slug": page["slug"],
            "title": page["title"],
            "html": encoded_html,
            "css": encoded_css,
            "js": encoded_js,
            "status": "publish",
            "auth_user": wp_user,
            "auth_pass": wp_pass,
            "is_base64": True
        }
        
        import urllib.parse
        query_params = {
            "wp_auth_user": wp_user,
            "wp_auth_pass": wp_pass
        }
        query_string = urllib.parse.urlencode(query_params)
        page_endpoint = f"{endpoint}?{query_string}"
        
        print(f"Deploying '{page['title']}' to {wp_url}/page/{page['slug']}...")
        
        # Include custom auth headers as fallback
        headers = {
            "X-Haus-Auth-User": wp_user,
            "X-Haus-Auth-Pass": wp_pass
        }
        
        try:
            response = session.post(page_endpoint, json=payload, headers=headers, timeout=20)
            if response.status_code == 200:
                print(f"  -> Response Headers: {dict(response.headers)}")
                result = response.json()
                if result.get("success"):
                    print(f"  -> Successfully deployed! ID: {result.get('page_id')} - URL: {result.get('url')}")
                    success_count += 1
                else:
                    print(f"  -> API returned error: {result}")
            else:
                print(f"  -> Failed with HTTP {response.status_code}: {response.text}")
        except Exception as e:
            print(f"  -> Connection error: {e}")

    print("\n==============================================")
    print(f"Deployment complete. Successfully pushed {success_count}/{len(pages)} pages.")
    
    if success_count > 0:
        print("\nNote: Make sure the pictures folders (wedding-pictures/ and senior-pictures/) ")
        print("are uploaded to your WordPress root directory (or matching public path) ")
        print("so the relative image links resolve correctly on the server.")

if __name__ == "__main__":
    sys.exit(main())
