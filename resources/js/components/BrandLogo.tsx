export function BrandLogo({ dark = false }: { dark?: boolean }) {
  return <img src="/brand/itechorbit-logo.svg?v=2" alt="iTechOrbit — Engineering with AI" width={300} height={84} className={`brand-logo${dark ? ' brand-logo-on-dark' : ''}`} />;
}
