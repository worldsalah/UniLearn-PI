#!/usr/bin/env python3
"""
Face encoding script using OpenCV and face_recognition library
Returns JSON with face encoding or error
"""

import sys
import json
import base64
import numpy as np
import cv2

try:
    import face_recognition
    FACE_RECOGNITION_AVAILABLE = True
except ImportError:
    FACE_RECOGNITION_AVAILABLE = False

def encode_face(image_path):
    """Detect face and return encoding"""
    try:
        # Load image
        image = cv2.imread(image_path)
        if image is None:
            return {'success': False, 'error': 'Could not load image'}
        
        # Convert BGR to RGB
        rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        
        if FACE_RECOGNITION_AVAILABLE:
            # Use face_recognition library (more accurate)
            face_locations = face_recognition.face_locations(rgb_image)
            if len(face_locations) == 0:
                return {'success': False, 'error': 'No face detected'}
            if len(face_locations) > 1:
                return {'success': False, 'error': 'Multiple faces detected'}
            
            face_encodings = face_recognition.face_encodings(rgb_image, face_locations)
            if len(face_encodings) == 0:
                return {'success': False, 'error': 'Could not encode face'}
            
            encoding = face_encodings[0].tolist()
            return {'success': True, 'encoding': encoding}
        else:
            # Fallback to OpenCV Haar Cascade (less accurate, only detection)
            face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
            faces = face_cascade.detectMultiScale(gray, 1.3, 5)
            
            if len(faces) == 0:
                return {'success': False, 'error': 'No face detected'}
            if len(faces) > 1:
                return {'success': False, 'error': 'Multiple faces detected'}
            
            # For Haar Cascade, we return face region as simple encoding
            (x, y, w, h) = faces[0]
            face_roi = gray[y:y+h, x:x+w]
            face_roi = cv2.resize(face_roi, (128, 128))
            encoding = face_roi.flatten().tolist()
            
            return {'success': True, 'encoding': encoding, 'method': 'opencv'}
            
    except Exception as e:
        return {'success': False, 'error': str(e)}

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'success': False, 'error': 'No image path provided'}))
        sys.exit(1)
    
    image_path = sys.argv[1]
    result = encode_face(image_path)
    print(json.dumps(result))
