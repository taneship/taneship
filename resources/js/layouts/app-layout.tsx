import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { AppSidebar } from '@/components/app-sidebar';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useTranslation } from '@/hooks/use-translation';

type AppLayoutProps = {
    children: ReactNode;
};

export function AppLayout({ children }: AppLayoutProps) {
    const { isSidebarOpen } = usePage().props;
    const { translate } = useTranslation();

    return (
        <TooltipProvider>
            <SidebarProvider defaultOpen={isSidebarOpen}>
                <AppSidebar />
                <SidebarInset>
                    <header className="flex h-12 shrink-0 items-center border-b px-4">
                        <SidebarTrigger aria-label={translate('foundation.sidebar.toggle')} />
                    </header>
                    <div className="flex flex-1 flex-col gap-6 p-6">{children}</div>
                </SidebarInset>
            </SidebarProvider>
        </TooltipProvider>
    );
}
