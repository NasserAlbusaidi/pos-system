# Frontend Design Guidelines

## Design Thinking

**Goal**: Create distinctive, production-grade interfaces. Avoid generic "AI slop" (standard Bootstrap/Material looks without soul).

### Core Principles
1.  **Bold Aesthetic**: Commit to a specific direction (Minimalist, Brutalist, Playful, Industrial, etc.). Don't float in the middle.
2.  **Intentionality**: Every pixel, margin, and color choice should have a reason.
3.  **Differentiation**: What makes this interface memorable?

## Aesthetics

### Typography
- **Selection**: Choose beautiful, unique fonts. Avoid generic system fonts (Arial, Roboto) unless intentional for a specific brutalist look.
- **Pairing**: Distinctive display font + refined body font.
- **Scale**: Use dramatic scale contrasts. Big text should be BIG.

### Color & Theme
- **Cohesion**: Use CSS variables or Tailwind config for a consistent palette.
- **Contrast**: Dominant colors with sharp accents.
- **Depth**: Avoid flat, solid colors everywhere. Use subtle gradients, noise textures, shadows, or layered transparencies to add depth.

### Motion & Interaction
- **Micro-interactions**: Hover states, focus states, and active states should feel "alive".
- **Performance**: Use CSS transforms/opacity for smooth 60fps animations.
- **Staggering**: Stagger element entry (e.g., list items fading in one by one) for a polished feel.

### Spatial Composition
- **Whitespace**: Use generous negative space or controlled density.
- **Grid**: Use grids, but don't be afraid to break them for impact (asymmetry, overlap).

## Implementation
- Match the implementation complexity to the design.
- If the design is "Playful", use spring physics for animations.
- If the design is "Industrial", use mono fonts and sharp borders.
