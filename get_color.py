
from PIL import Image
from collections import Counter

def get_dominant_color(image_path):
    try:
        img = Image.open(image_path)
        img = img.convert("RGB")
        img = img.resize((50, 50))
        
        pixels = list(img.getdata())
        counts = Counter(pixels)
        
        # Get the most common color
        # We might want to ignore white/black if they are backgrounds, but let's see.
        common = counts.most_common(10)
        
        for color, count in common:
            # simple filter to avoid pure white/black if they are background
            if sum(color) > 750 or sum(color) < 20: 
                continue
            
            hex_color = '#{:02x}{:02x}{:02x}'.format(*color)
            print(f"Dominant Color: {hex_color} (RGB: {color})")
            return hex_color
            
        # If all filtered, just take the absolute most common
        color = common[0][0]
        hex_color = '#{:02x}{:02x}{:02x}'.format(*color)
        print(f"Dominant Color: {hex_color} (RGB: {color})")
        return hex_color

    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    get_dominant_color("WhatsApp Image 2025-12-21 at 22.30.07.jpeg")
