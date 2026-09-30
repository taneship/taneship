import type { ReactNode } from 'react';

type AppLayoutProps = {
    children: ReactNode;
};

export function AppLayout({ children }: AppLayoutProps) {
    return (
        <main className="mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-6 p-6">
            {children}
        </main>
    );
}
