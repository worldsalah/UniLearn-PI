#!/usr/bin/env python3
"""
Face comparison script
Compare two face encodings and return distance/match result
Supports both direct JSON arguments and file paths for large encodings
"""

import sys
import json
import os
import numpy as np

try:
    import face_recognition
    FACE_RECOGNITION_AVAILABLE = True
except ImportError:
    FACE_RECOGNITION_AVAILABLE = False

def load_encoding(arg):
    """Load encoding from either JSON string or file path"""
    # Check if it's a file path
    if os.path.isfile(arg):
        with open(arg, 'r') as f:
            return json.load(f)
    else:
        # Try to parse as JSON string
        return json.loads(arg)

def compare_faces(encoding1, encoding2):
    """Compare two face encodings"""
    try:
        if FACE_RECOGNITION_AVAILABLE and len(encoding1) == 128:
            # Use face_recognition library (128-dimension encoding)
            face_encoding1 = np.array(encoding1)
            face_encoding2 = np.array(encoding2)
            
            # Calculate Euclidean distance
            distance = np.linalg.norm(face_encoding1 - face_encoding2)
            
            # face_recognition compare uses 0.6 threshold by default
            match = distance < 0.6
            
            return {
                'success': True,
                'distance': float(distance),
                'match': bool(match)
            }
        else:
            # Fallback: Simple comparison for OpenCV encodings
            arr1 = np.array(encoding1)
            arr2 = np.array(encoding2)
            
            # Normalize and compare
            arr1 = arr1 / np.linalg.norm(arr1)
            arr2 = arr2 / np.linalg.norm(arr2)
            
            # Calculate cosine distance
            similarity = np.dot(arr1, arr2)
            distance = 1 - similarity
            
            return {
                'success': True,
                'distance': float(distance),
                'match': bool(distance < 0.4)
            }
            
    except Exception as e:
        return {'success': False, 'error': str(e)}

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print(json.dumps({'success': False, 'error': 'Two encodings required (JSON strings or file paths)'}))
        sys.exit(1)
    
    try:
        encoding1 = load_encoding(sys.argv[1])
        encoding2 = load_encoding(sys.argv[2])
        result = compare_faces(encoding1, encoding2)
    except Exception as e:
        result = {'success': False, 'error': f'Failed to load encodings: {str(e)}'}
    
    print(json.dumps(result))
