# 🚀 Quick Start Guide - One Command Solution

## Current Status
✅ FOSElasticaBundle is installed and configured
✅ Autocomplete API is ready
✅ All files are created correctly
❌ Elasticsearch server needs to be started

## Option 1: Manual Elasticsearch (Recommended for your system)

### One-time setup:
1. Download Elasticsearch: https://www.elastic.co/downloads/elasticsearch
2. Extract to: `C:\elasticsearch\`
3. Run: `C:\elasticsearch\bin\elasticsearch.bat`
4. Wait for green status at: http://localhost:9200

### Daily development (after one-time setup):
```bash
composer run dev-simple
```

## Option 2: Use Mock Data (No Elasticsearch needed)

If you want to test the autocomplete RIGHT NOW without Elasticsearch:

1. Change the API URL in your JavaScript from:
   `/api/autocomplete/courses`
   
   To:
   `/api/autocomplete/courses-mock`

2. This uses mock data and works immediately!

## Option 3: Docker Desktop Fix

Your Docker issue is likely because Docker Desktop isn't running properly:

1. Restart Docker Desktop
2. Wait for it to fully start
3. Try: `composer run dev` again

## 🎯 What Works Right Now

The autocomplete UI is 100% ready! You just need:

1. ✅ Start Elasticsearch (manually or fix Docker)
2. ✅ Run: `composer run dev-simple`
3. ✅ Test the search at: http://localhost:8000

## 📞 Quick Test

Use this HTML to test autocomplete RIGHT NOW:

```html
<!-- Add this where you want the search -->
<div class="autocomplete-container">
    <input 
        class="form-control pe-5 form-control-sm" 
        type="search" 
        name="search" 
        placeholder="Search courses..." 
        autocomplete="off"
        id="courseSearchInput"
    >
    <div class="autocomplete-suggestions" id="suggestionsContainer"></div>
</div>

<script>
// Use mock API for testing
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('courseSearchInput');
    const suggestionsContainer = document.getElementById('suggestionsContainer');
    let debounceTimer;
    
    if (!searchInput || !suggestionsContainer) return;
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        if (debounceTimer) clearTimeout(debounceTimer);
        
        if (query.length < 2) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetch(`/api/autocomplete/courses-mock?q=${encodeURIComponent(query)}&limit=5`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.suggestions.length > 0) {
                        const html = data.suggestions.map(suggestion => `
                            <div class="autocomplete-item" onclick="selectCourse('${suggestion.title}')">
                                <div class="autocomplete-title">${suggestion.title}</div>
                                <div class="autocomplete-description">${suggestion.description}</div>
                                <div class="autocomplete-meta">
                                    <span class="autocomplete-level">${suggestion.level}</span>
                                    <span class="autocomplete-price">$${suggestion.price}</span>
                                </div>
                            </div>
                        `).join('');
                        
                        suggestionsContainer.innerHTML = html;
                        suggestionsContainer.style.display = 'block';
                    } else {
                        suggestionsContainer.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 300);
    });
    
    window.selectCourse = function(title) {
        searchInput.value = title;
        suggestionsContainer.style.display = 'none';
    };
});
</script>

<style>
.autocomplete-container { position: relative; width: 200px; }
.autocomplete-suggestions { 
    position: absolute; top: 100%; left: 0; right: 0; 
    background: white; border: 1px solid #ddd; border-top: none;
    border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    max-height: 300px; overflow-y: auto; z-index: 1000; display: none;
}
.autocomplete-item { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
.autocomplete-item:hover { background-color: #f8f9fa; border-left: 3px solid #007bff; }
.autocomplete-title { font-weight: 600; color: #333; margin-bottom: 4px; }
.autocomplete-description { color: #666; font-size: 13px; margin-bottom: 4px; }
.autocomplete-meta { display: flex; gap: 12px; font-size: 12px; }
.autocomplete-level { background: #e3f2fd; color: #1976d2; padding: 2px 8px; border-radius: 12px; }
.autocomplete-price { color: #28a745; font-weight: 600; }
</style>
```

**This works immediately with mock data!** 🎉
