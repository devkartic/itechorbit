import { Link } from '@inertiajs/react';
import { FileQuestion, LayoutDashboard, ShieldAlert } from 'lucide-react';
import AdminLayout, { type Navigation } from '@/layouts/admin/AdminLayout';
import { Button } from '@/components/ui/button';

export default function AdminError({ status, navigation }: { status: number; navigation: Navigation }) {
  const forbidden = status === 403;
  const title = forbidden ? 'Access restricted' : 'Page not found';
  const Icon = forbidden ? ShieldAlert : FileQuestion;

  return <AdminLayout title={title} navigation={navigation}>
    <section className="workspace-error" aria-labelledby="workspace-error-title">
      <div className="workspace-error-icon"><Icon size={32} aria-hidden="true" /></div>
      <p className="workspace-eyebrow">Error {status}</p>
      <h1 id="workspace-error-title">{title}</h1>
      <p className="workspace-error-description">{forbidden
        ? 'You do not have permission to access this page. Contact your administrator if you need access.'
        : 'This workspace page could not be found. It may have moved or the item may no longer exist.'}</p>
      <Button asChild><Link href="/home"><LayoutDashboard size={17} aria-hidden="true" />Back to dashboard</Link></Button>
    </section>
  </AdminLayout>;
}
