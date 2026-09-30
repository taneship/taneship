import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboardIcon } from 'lucide-react';

import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

export function AppSidebar() {
    const { translate } = useTranslation();
    const { url } = usePage();

    const items = [
        {
            label: translate('foundation.sidebar.dashboard'),
            route: dashboard(),
            icon: LayoutDashboardIcon,
        },
    ];

    return (
        <Sidebar collapsible="icon">
            <SidebarContent>
                <nav aria-label={translate('foundation.sidebar.navigation')}>
                    <SidebarGroup>
                        <SidebarMenu>
                            {items.map((item) => (
                                <SidebarMenuItem key={item.route.url}>
                                    <SidebarMenuButton
                                        render={<Link href={item.route} />}
                                        isActive={url === item.route.url}
                                        tooltip={item.label}
                                    >
                                        <item.icon />
                                        <span>{item.label}</span>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                </nav>
            </SidebarContent>
        </Sidebar>
    );
}
