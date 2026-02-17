# GitHub Setup Guide for Unilearn Project

## Step-by-Step Instructions

### 1. Create the GitHub Workflows Folder (Windows)

**Method 1: Using File Explorer**
1. Open your project folder: `c:\xampp\htdocs\projectweb\Unilearn`
2. Navigate to the `.github` folder (if it doesn't exist, create it)
3. Inside `.github`, create a new folder called `workflows`

**Method 2: Using Command Prompt**
```cmd
cd c:\xampp\htdocs\projectweb\Unilearn
mkdir .github\workflows
```

### 2. Add the CI File

The CI file (`.github\workflows\ci.yml`) is already created in your project. It includes:
- PHP 8.2 setup
- Composer dependency installation
- PHP syntax checking
- PHPUnit test execution

### 3. Push Changes in a Setup Branch

1. **Create and switch to a setup branch:**
```cmd
git checkout -b setup-github-workflow
```

2. **Add the new files:**
```cmd
git add .github/workflows/ci.yml
git add .github/pull_request_template.md
git add GITHUB-SETUP-GUIDE.md
```

3. **Commit the changes:**
```cmd
git commit -m "Add GitHub Actions CI workflow and PR template"
```

4. **Push to GitHub:**
```cmd
git push origin setup-github-workflow
```

### 4. Create a Pull Request

1. Go to your GitHub repository
2. Click "Compare & pull request" for the `setup-github-workflow` branch
3. Fill in the PR details using the template
4. Click "Create pull request"

### 5. Protect the Main Branch

**Important:** Only the repository owner can do this!

1. Go to your GitHub repository
2. Click **Settings** tab
3. Click **Branches** in the left menu
4. Click **Add branch protection rule**
5. Under "Branch name pattern", type `main`
6. Check these boxes:
   - ✅ **Require pull request reviews before merging**
   - ✅ **Require status checks to pass before merging**
   - ✅ **Require branches to be up to date before merging**
7. In "Required number of approvals", set to **1**
8. In "Require status checks to pass", select:
   - ✅ **CI** (this will appear after your first PR runs)
9. Click **Create** or **Save changes**

**Result:** Nobody can push directly to main anymore. All changes must go through PRs with approval and passing CI.

## Team Workflow for 5 Students

### Branch Naming Convention

Use this format: `type-description`

**Types:**
- `feature-` - New features (e.g., `feature-user-login`)
- `fix-` - Bug fixes (e.g., `fix-database-connection`)
- `refactor-` - Code improvements (e.g., `refactor-user-controller`)
- `docs-` - Documentation (e.g., `docs-api-endpoints`)

### Daily Workflow

1. **Start Work:**
```cmd
git checkout main
git pull origin main
git checkout -b feature-your-feature-name
```

2. **Work on your feature**
3. **Push your branch:**
```cmd
git push origin feature-your-feature-name
```

4. **Create Pull Request**
   - Go to GitHub
   - Click "New pull request"
   - Select your branch
   - Fill out the PR template
   - Assign reviewers (ask 1-2 teammates to review)

5. **Review Process**
   - Each student should review at least 1 PR per day
   - Leave constructive comments
   - Check for:
     - Code follows project standards
     - Tests are included
     - Documentation is updated
     - No obvious bugs

6. **Merge**
   - Wait for CI to pass ✅
   - Wait for at least 1 approval 👍
   - Click "Merge pull request"
   - Delete your branch

### Important Rules

- **NEVER** push directly to main
- **ALWAYS** create a branch for each feature/fix
- **ALWAYS** write a clear PR description
- **ALWAYS** review your teammates' work
- **ALWAYS** test your changes before creating PR

### Troubleshooting

**If CI fails:**
1. Check the CI logs in your PR
2. Fix the error locally
3. Push the fix to your branch
4. CI will automatically re-run

**If you have merge conflicts:**
1. Update your branch:
```cmd
git checkout main
git pull origin main
git checkout your-branch-name
git merge main
```
2. Fix conflicts locally
3. Commit and push
4. PR will update automatically

This workflow keeps your code clean, tested, and ensures everyone reviews each other's work!
