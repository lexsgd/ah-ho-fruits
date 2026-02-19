# Push to GitHub Instructions

Run these commands in your terminal from the `ah-ho-fruits` folder:

```bash
# Navigate to the project folder
cd "ah-ho-fruits"

# Initialize git (if not already done)
git init
git branch -m main

# Configure git user (use your details)
git config user.email "lex@peacom.co"
git config user.name "Lex"

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: Ah Ho Fruit WordPress theme and deployment setup"

# Add remote
git remote add origin https://github.com/lexsgd/ah-ho-fruits.git

# Push to GitHub
git push -u origin main
```

After pushing, the GitHub Actions workflow will automatically deploy to Vodien!
