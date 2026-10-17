# Placement
جایگاه‌های بنر سایت که سوپراپ از طریق API پر می‌کند.

- هر جایگاه یک `key` و اسپک ثابت دارد (`Placement::spec()`)
- در قالب: `<x-placement key="home.hero" />` یا `<x-placement key="category.header" :context-id="$category->id" />`
- بنر فقط در وضعیت approved و داخل بازه‌ی زمانی نمایش داده می‌شود
