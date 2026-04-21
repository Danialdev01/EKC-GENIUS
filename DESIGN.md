# EKC Genius IR4.0 — Design System & UI Specification

> **Stack**: PHP · Tailwind CSS v3 (CLI) · Minimal vanilla JS · Google Fonts (Poppins + Inter)
> **Philosophy**: Card-first, mobile-first, Tailwind-first. All layout, spacing, color, and typography decisions are expressed via Tailwind utility classes.

---

## 1. Brand Identity

| Property | Value |
|---|---|
| Brand Name | **EKC Genius** |
| Tagline | *Empowering Early Childhood Education with IR4.0 Technology* |
| Personality | Innovative · Professional · Inclusive · AI-Driven |
| Primary Market | Early childhood centers, autism centers, special education |
| Visual Character | Soft card-centric UI, vibrant indigo + amber accents, tactile elevation |

**Logo**: Brain icon + wordmark — `EKC` in `#1E293B` (Poppins 700) + `Genius` in `#6366F1` (Poppins 700)

```html
<a href="/" class="flex items-center gap-2 font-poppins text-xl font-bold select-none">
  <span class="text-3xl">🧠</span>
  <span class="text-slate-800">EKC</span>
  <span class="text-indigo-500">Genius</span>
</a>
```

---

## 2. Color System

### Core Surfaces

| Role | Class | Hex | Usage |
|---|---|---|---|
| Page Background | `bg-paper` | `#FAFBFC` | `<body>`, page wrappers |
| Card / Surface | `bg-white` | `#FFFFFF` | All elevated cards |
| Sidebar Background | `bg-slate-900` | `#0F172A` | Sidebar nav |

### Text

| Role | Class | Hex | Usage |
|---|---|---|---|
| Primary Text | `text-slate-800` | `#1E293B` | Headings, important body |
| Secondary Text | `text-slate-500` | `#64748B` | Subheadings, captions |
| Muted Text | `text-slate-400` | `#94A3B8` | Placeholder, timestamps |
| Inverted Text | `text-white` | `#FFFFFF` | On dark backgrounds |

### Accent Colors

| Role | Class | Hex | Usage |
|---|---|---|---|
| Primary CTA | `bg-indigo-500` | `#6366F1` | Buttons, active states |
| Primary Hover | `bg-indigo-600` | `#4F46E5` | Button hover states |
| Fun Accent | `bg-amber-400` | `#FBBF24` | Badges, highlights |

### Semantic Colors

| Role | Class | Hex | Usage |
|---|---|---|---|
| Success | `text-emerald-500` | `#10B981` | Completion, correct |
| Warning | `text-orange-500` | `#F97316` | Deadlines, warnings |
| Danger | `text-red-500` | `#EF4444` | Errors, delete |

---

## 3. Typography

### Font Stack

- `font-poppins` → `'Poppins', 'Quicksand', 'Montserrat', sans-serif`
- `font-inter` → `'Inter', 'Roboto', 'Helvetica Neue', Arial, sans-serif`

### Type Scale

| Role | Tailwind Classes | Usage |
|---|---|---|
| Hero Title | `font-poppins text-4xl md:text-5xl font-bold` | Landing hero |
| Heading 2 | `font-poppins text-2xl md:text-3xl font-semibold` | Section titles |
| Heading 3 | `font-poppins text-lg font-semibold` | Card titles |
| Body Base | `font-inter text-base text-slate-600` | Standard paragraphs |
| Body Medium | `font-inter text-base font-medium` | Buttons, nav |
| Caption | `font-inter text-sm text-slate-500` | Metadata |

---

## 4. Spacing & Layout

- Max width: `max-w-7xl` (1280px)
- Section padding: `py-16 md:py-20 lg:py-24`
- Card padding: `p-6` (desktop), `p-4` (mobile)

---

## 5. Tailwind Config Extension

```js
// tailwind.config.js
module.exports = {
  content: ['./**/*.{php,html}'],
  theme: {
    extend: {
      fontFamily: {
        poppins: ['Poppins', 'Quicksand', 'Montserrat', 'sans-serif'],
        inter: ['Inter', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
      },
      colors: {
        paper: '#FAFBFC',
      },
      boxShadow: {
        'card': '0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03)',
        'card-hover': '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05)',
        'indigo-glow': '0 4px 14px 0 rgba(99,102,241,0.39)',
      },
      borderRadius: {
        'pill': '999px',
      },
    },
  },
  plugins: [],
}
```

---

## 6. Components

### Buttons

**Primary (Indigo Pill)**
```html
<button class="bg-indigo-500 hover:bg-indigo-600 text-white font-inter text-base font-medium px-7 py-3 rounded-pill shadow-indigo-glow hover:-translate-y-0.5 transition-all">
  Login
</button>
```

**Secondary (Outline)**
```html
<button class="border-2 border-slate-200 hover:border-indigo-400 text-slate-800 font-inter text-base font-medium px-7 py-3 rounded-pill hover:-translate-y-0.5 transition-all">
  Learn More
</button>
```

### Input Fields
```html
<input type="text" class="w-full bg-white text-slate-800 placeholder-slate-400 font-inter text-base px-4 py-3.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all" />
```

### Cards
```html
<div class="bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300">
  <!-- content -->
</div>
```

---

## 7. Sidebar (Dashboard Navigation)

- Width: `w-64`
- Background: `bg-slate-900`
- Active nav: `bg-indigo-500/20 text-indigo-300`
- Inactive nav: `text-slate-400 hover:text-white hover:bg-slate-800`

---

## 8. Landing Page Sections

1. **Navbar** — Logo · Nav links (desktop) · Login button
2. **Hero** — Badge + H1 + subtitle + CTA buttons + stats
3. **Features** — 3-column feature card grid
4. **Statistics** — Key metrics strip
5. **CTA Banner** — Full-width indigo section
6. **Footer** — Logo · links · copyright

---

## 9. Login Page Layout

- Centered card with role selector
- Admin: username + password fields
- Teacher: searchable select (TomSelect) + passkey
- Parent: searchable select (TomSelect) for child's name