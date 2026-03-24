# 3. Competitive Analysis

## 3.1 Market Map

```
                        Framework-Specific ◄──────────────────► Framework-Agnostic
                              │                                        │
              ┌───────────────┼────────────────┐                       │
       Free   │  Telescope    │  Symfony Prof.  │                   ★ ADP ★
              │  Debugbar     │  Clockwork      │                      │
              ├───────────────┼────────────────┤                       │
       Paid   │  Ray ($49)    │                 │                      │
              │  Blackfire    │  Tideways       │                      │
              └───────────────┴────────────────┘                       │
```

**ADP occupies a unique niche**: free, framework-agnostic, with functionality exceeding paid alternatives.

## 3.2 Feature Matrix (Competitive Comparison)

| Feature | ADP | Telescope | Symfony Prof. | Clockwork | Ray | Debugbar |
|---------|:---:|:---------:|:-------------:|:---------:|:---:|:--------:|
| Multi-framework | **4** | 1 | 1 | 3 | 2 | 1 |
| Auto-collectors | **28** | 14 | ~12 | ~10 | 0 | ~8 |
| Live Inspector | **20+** | 0 | 0 | 0 | 0 | 0 |
| Real-time SSE | yes | no | no | no | yes | no |
| UDP streaming | yes | no | no | no | no | no |
| Code generation | yes | no | no | no | no | no |
| Git integration | yes | no | no | no | no | no |
| DB browser | yes | no | no | no | no | no |
| File explorer | yes | no | no | no | no | no |
| Command palette | yes | no | no | no | no | no |
| Dark mode | yes | no | partial | n/a | yes | no |
| PWA/Offline | yes | no | no | no | no | no |
| Multi-app | yes | no | no | no | no | no |
| Language-agnostic | yes | no | no | no | partial | no |
| Open source | yes | yes | yes | yes | no | yes |
| cURL builder | yes | no | no | no | no | no |
| Request replay | yes | no | no | no | no | no |
| Fuzzy search | yes | no | no | no | no | no |
| i18n editor | yes | no | no | no | no | no |

## 3.3 Key Takeaways

- **vs Telescope**: ADP surpasses it by 2-3x in features + works beyond Laravel
- **vs Symfony Profiler**: ADP delivers superior UX (SPA, dark mode, command palette) + Inspector
- **vs Clockwork**: ADP is a full platform vs a lightweight extension
- **vs Ray ($49/year)**: ADP is free with automatic data collection vs manual dump()
- **vs Debugbar**: ADP is an SPA with Inspector and multi-framework support

---

## Actions

- [ ] Write a blog post "ADP vs Telescope vs Clockwork: Honest comparison" for dev.to and Reddit
- [ ] Prepare a comparison table for the GitHub README (simplified version)
- [ ] Validate competitor data (current versions, new features)
- [ ] Monitor competitor updates monthly
