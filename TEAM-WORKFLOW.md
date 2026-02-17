# Team Workflow Guide

## 🔄 Branch Strategy

### **Branch Naming Convention**
- **Student branches:** `student/name-feature` (e.g., `student/ali-user-profile`)
- **Feature branches:** `feature/description` (e.g., `feature/quiz-system`)
- **Bug fixes:** `fix/description` (e.g., `fix/login-issue`)
- **Integration branch:** `setup/ci-pr` (testing ground)
- **Production branch:** `main` (always stable)

## 📋 Step-by-Step Workflow

### **For Students (Daily Work):**

1. **Create your branch:**
```bash
git checkout -b student/yourname-feature-name
```

2. **Do your work:**
- Code your feature
- Test locally
- Commit changes with clear messages

3. **Push your branch:**
```bash
git push origin student/yourname-feature-name
```

4. **Create Pull Request to `setup/ci-pr`:**
- Go to GitHub
- Click "New pull request"
- Base: `setup/ci-pr` ← Compare: `student/yourname-feature-name`
- Fill PR template
- Submit

### **For Team Lead/Reviewer:**

5. **Review and Merge:**
- Check CI passed ✅
- Review code changes
- If approved: merge to `setup/ci-pr`

6. **Test Integration:**
- `setup/ci-pr` contains all approved changes
- CI runs automatically on merge
- If everything works: push to `main`

7. **Deploy to Main:**
```bash
git checkout setup/ci-pr
git pull origin setup/ci-pr
git checkout main
git merge setup/ci-pr
git push origin main
```

## 🎯 **Why This Workflow is Great:**

### **✅ Benefits:**
- **Quality Control:** Every change tested in `setup/ci-pr`
- **Team Safety:** No direct pushes to `main`
- **Easy Rollback:** If something breaks, revert `setup/ci-pr`
- **Clear History:** Clean, organized git history
- **Team Collaboration:** Students work independently

### **✅ CI Triggers:**
- **Student branches:** CI runs on push
- **PR to setup/ci-pr:** CI runs on PR
- **Merge to setup/ci-pr:** CI validates integration
- **Merge to main:** Final CI check

## 🚀 **Example Scenario:**

### **Student Ali wants to add quiz feature:**

1. **Ali creates branch:**
```bash
git checkout -b student/ali-quiz-feature
```

2. **Ali codes and pushes:**
```bash
git add .
git commit -m "Add quiz question validation"
git push origin student/ali-quiz-feature
```

3. **Ali creates PR:**
- Base: `setup/ci-pr` ← Compare: `student/ali-quiz-feature`
- CI runs automatically ✅

4. **Team Lead reviews:**
- CI passed ✅
- Code looks good ✅
- Clicks "Merge pull request"

5. **Changes now in `setup/ci-pr`:**
- CI runs again on integration ✅
- All student features combined

6. **Ready for main:**
```bash
git checkout main
git merge setup/ci-pr
git push origin main
```

## 📝 **Rules to Follow:**

### **❌ Don't Do:**
- Never push directly to `main`
- Never merge broken code to `setup/ci-pr`
- Never skip code review

### **✅ Always Do:**
- Create descriptive branch names
- Write clear commit messages
- Test before pushing
- Review teammates' code
- Wait for CI to pass

## 🛠️ **Useful Commands:**

### **Check Status:**
```bash
git status
git branch -a
```

### **Sync with Team:**
```bash
git checkout setup/ci-pr
git pull origin setup/ci-pr
```

### **Clean Up:**
```bash
# After merge, delete your branch
git branch -d student/yourname-feature
git push origin --delete student/yourname-feature
```

## 🎉 **Result:**
- **Clean main branch** - always working
- **Happy team** - organized workflow
- **Quality code** - always tested
- **Easy tracking** - clear history

This workflow is used by professional teams and perfect for university projects!
