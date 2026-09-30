import { Head, usePage } from '@inertiajs/react';

export default function Welcome() {
  const { name } = usePage().props;

  return (
    <>
      <Head title="Welcome" />
      <main className="flex min-h-screen items-center justify-center">
        <h1 className="text-4xl font-semibold">{name}</h1>
      </main>
    </>
  );
}
