
from PIL import Image
try:
    import pytesseract
except ImportError:
    print("pytesseract not installed, skipping OCR")
    pytesseract = None

from collections import Counter
import re

def get_palette_and_text(image_path):
    print(f"Analyzing {image_path}...")
    try:
        img = Image.open(image_path)
        img = img.convert("RGB")
        
        # 1. Color Analysis (Cluster into simpler palette)
        # Resize for speed
        small_img = img.resize((150, 150)) 
        pixels = list(small_img.getdata())
        
        # Simple clustering by rounding values to group similar colors
        def rounded_color(c):
            return (round(c[0]/30)*30, round(c[1]/30)*30, round(c[2]/30)*30)
            
        rounded_pixels = [rounded_color(p) for p in pixels]
        counts = Counter(rounded_pixels)
        
        print("\n--- Detected Colors ---")
        common = counts.most_common(10)
        
        for color, count in common:
            # Filter out very white or very black if they are just background/text
            if sum(color) > 700 or sum(color) < 20: 
                continue
                
            hex_color = '#{:02x}{:02x}{:02x}'.format(*color)
            print(f"Color: {hex_color} (RGB: {color}) - Count: {count}")

        # 2. Text Extraction
        print("\n--- Detected Text ---")
        if pytesseract:
            # Try to increase contrast for better OCR
            # This is a basic attempt
            text = pytesseract.image_to_string(img)
            clean_text = text.strip()
            if clean_text:
                print(clean_text)
            else:
                print("No text detected by OCR.")
        else:
            print("OCR skipped (pytesseract not available). Please read text manually from image if needed.")

    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    get_palette_and_text("WhatsApp Image 2025-12-21 at 22.30.07.jpeg")
