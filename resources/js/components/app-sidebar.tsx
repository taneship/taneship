import { Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboardIcon } from 'lucide-react';
import { useEffect } from 'react';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { UserMenu } from '@/components/user-menu';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

export function AppSidebar() {
    const { translate } = useTranslation();
    const { url, props } = usePage();
    const { setOpenMobile } = useSidebar();

    // The layout outlives a visit: on a phone, the sheet would stay open over the next page.
    useEffect(() => router.on('navigate', () => setOpenMobile(false)), [setOpenMobile]);

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
            {props.user !== null && (
                <SidebarFooter>
                    <UserMenu user={props.user} />
                </SidebarFooter>
            )}
        </Sidebar>
    );
}
