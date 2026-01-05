# ایجاد فایل .gitattributes
echo "# Auto detect text files and perform LF normalization
* text=auto

# فایل‌های بچ ویندوز باید CRLF بمانند
*.bat text eol=crlf

# فایل‌های خاص
*.php text
*.js text
*.css text
*.html text
*.json text
*.md text
*.txt text
*.yml text
*.yaml text
*.xml text
*.sql text

# فایل‌های باینری
*.png binary
*.jpg binary
*.jpeg binary
*.gif binary
*.ico binary
*.pdf binary
*.zip binary
*.tar.gz binary
" > .gitattributes