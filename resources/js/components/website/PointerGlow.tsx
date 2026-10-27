import { useEffect, useRef } from 'react';

const SEGMENTS = 32;
const LIFETIME = 450;

/** A lightweight following trail: fixed-step motion and no React renders per frame. */
export function PointerGlow() {
  const glow = useRef<HTMLDivElement>(null);
  const line = useRef<SVGSVGElement>(null);
  useEffect(() => {
    const element = glow.current;
    const svg = line.current;
    if (!element || !svg) return;
    const paths = [...svg.querySelectorAll('path')];
    const preference = window.matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)');
    let frame = 0;
    type Point = { x: number; y: number };
    let points: Point[] = [];
    let target: Point = { x: 0, y: 0 };
    let lastMove = 0;
    let previousFrame = 0;
    let accumulated = 0;
    const step = 1000 / 120;
    const hide = () => {
      element.dataset.moving = 'false';
      cancelAnimationFrame(frame);
      frame = 0;
      points = [];
      accumulated = 0;
      for (const path of paths) path.removeAttribute('d');
    };
    const draw = (now: number) => {
      const idle = Math.max(0, now - lastMove);
      if (idle >= LIFETIME || !preference.matches) { hide(); return; }
      accumulated += Math.min(50, Math.max(0, now - previousFrame));
      previousFrame = now;
      // Fixed simulation steps preserve the same trailing feel on 60/120 Hz screens.
      while (accumulated >= step) {
        points[0] = { ...target };
        const follow = 0.34 + 0.32 * (idle / LIFETIME);
        for (let i = 1; i < points.length; i++) {
          points[i].x += (points[i - 1].x - points[i].x) * follow;
          points[i].y += (points[i - 1].y - points[i].y) * follow;
        }
        accumulated -= step;
      }
      const fade = (1 - idle / LIFETIME) ** 1.25;
      for (let i = 0; i < SEGMENTS; i++) {
        const current = points[i];
        const next = points[i + 1];
        const previous = points[i - 1] ?? current;
        const following = points[i + 2] ?? next;
        const c1x = current.x + (next.x - previous.x) / 6;
        const c1y = current.y + (next.y - previous.y) / 6;
        const c2x = next.x - (following.x - current.x) / 6;
        const c2y = next.y - (following.y - current.y) / 6;
        paths[i].setAttribute('d', `M ${current.x} ${current.y} C ${c1x} ${c1y} ${c2x} ${c2y} ${next.x} ${next.y}`);
        const strength = (1 - i / SEGMENTS) ** 1.7;
        paths[i].setAttribute('opacity', String(0.95 * fade * strength));
        // Our violet palette softens to a smoky lavender tail, without a cursor ring.
        paths[i].setAttribute('stroke', `rgb(${Math.round(128 + 48 * strength)} ${Math.round(86 + 57 * strength)} ${Math.round(204 + 43 * strength)})`);
      }
      element.dataset.moving = 'true';
      frame = requestAnimationFrame(draw);
    };
    const move = (event: PointerEvent) => {
      if (!preference.matches || event.pointerType !== 'mouse' || (event.movementX === 0 && event.movementY === 0)) return;
      target = { x: event.clientX, y: event.clientY };
      lastMove = performance.now();
      if (!points.length) points = Array.from({ length: SEGMENTS + 1 }, () => ({ ...target }));
      if (!frame) {
        previousFrame = lastMove;
        frame = requestAnimationFrame(draw);
      }
    };
    window.addEventListener('pointermove', move, { passive: true });
    document.documentElement.addEventListener('pointerleave', hide);
    window.addEventListener('blur', hide);
    window.addEventListener('pointerdown', hide, { passive: true });
    window.addEventListener('keydown', hide);
    window.addEventListener('scroll', hide, { passive: true });
    document.addEventListener('visibilitychange', hide);
    preference.addEventListener('change', hide);
    return () => {
      hide();
      window.removeEventListener('pointermove', move);
      document.documentElement.removeEventListener('pointerleave', hide);
      window.removeEventListener('blur', hide);
      window.removeEventListener('pointerdown', hide);
      window.removeEventListener('keydown', hide);
      window.removeEventListener('scroll', hide);
      document.removeEventListener('visibilitychange', hide);
      preference.removeEventListener('change', hide);
    };
  }, []);
  return <div ref={glow} className="pointer-glow" data-moving="false" aria-hidden="true"><svg ref={line} className="pointer-glow-canvas" width="100%" height="100%">{Array.from({ length: SEGMENTS }, (_, index) => <path key={index} className="pointer-glow-line" fill="none" strokeWidth="1" strokeLinecap="round" strokeLinejoin="round" />)}</svg></div>;
}
