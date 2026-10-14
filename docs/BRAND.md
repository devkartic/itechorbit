# iTechOrbit visual identity

The final identity is a single wordmark image with a subtle orbit around the O in Orbit and the tagline ENGINEERING WITH AI. The standalone planet illustration has been removed at the user's request. The business name remains the primary focus at header size.

Palette: deep navy `#211A35`, violet `#7040CF`, pale violet `#A98BE8` and a small mint `#AFE1D0` orbital node. Keep the logo stationary and preserve its 300:84 aspect ratio. Public and workspace logos use the same master image. On dark backgrounds, invert/hue-rotate styling preserves the artwork and creates a readable pale violet treatment without another design file.

Assets:

- `public/brand/itechorbit-logo.svg`: single transparent master image containing the name, orbit and tagline.
- `public/brand/itechorbit-logo.png`: a raster export of the same artwork for the existing email template (SVG support varies by email client).
- `public/favicon.svg`: compact orbital O for browser tabs.
- `public/apple-touch-icon.png`: compact orbital O for saved mobile shortcuts.
- `docs/assets/itechorbit-brand-preview.png`: light/dark presentation preview.

The master SVG embeds the existing self-hosted Inter bold font, so it renders as a complete image without fetching a separate font. BrandLogo in React and the brand-logo Blade component share this asset. The full image replaces public header/footer, authentication, admin/workspace, retained legacy template and email branding. Legacy personal portfolio names remain intact. Browser/tab icons use the compact O because a full wordmark is unreadable at those sizes. No remote font or new application dependency is required.
