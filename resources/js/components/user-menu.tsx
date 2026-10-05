import { Link } from '@inertiajs/react';
import { ChevronsUpDownIcon, LogOutIcon, SettingsIcon, UserRoundIcon } from 'lucide-react';
import { useRef } from 'react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { logout } from '@/routes';
import account from '@/routes/account';
import type { User } from '@/types/user';

type UserMenuProps = {
    user: User;
};

export function UserMenu({ user }: UserMenuProps) {
    const { translate } = useTranslation();
    const { isMobile } = useSidebar();
    const navigationRef = useRef<HTMLElement>(null);

    return (
        <nav ref={navigationRef} aria-label={translate('identity.user_menu.label')}>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            render={
                                <SidebarMenuButton
                                    size="lg"
                                    className="data-popup-open:bg-sidebar-accent data-popup-open:text-sidebar-accent-foreground"
                                />
                            }
                        >
                            <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-sidebar-accent text-sidebar-accent-foreground">
                                <UserRoundIcon />
                            </span>
                            <span className="grid flex-1 leading-tight">
                                <span className="truncate font-medium">{user.name}</span>
                                <span className="truncate text-xs">{user.email}</span>
                            </span>
                            <ChevronsUpDownIcon className="ml-auto" />
                        </DropdownMenuTrigger>
                        {/* The popup stays in the nav: outside every landmark, it fails axe's region rule. */}
                        <DropdownMenuContent
                            container={navigationRef}
                            side={isMobile ? 'bottom' : 'right'}
                            align="end"
                            className="min-w-56"
                        >
                            <DropdownMenuItem render={<Link href={account.profile.edit()} />}>
                                <SettingsIcon />
                                {translate('identity.user_menu.account_settings')}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                nativeButton
                                render={<Link href={logout()} as="button" className="w-full" />}
                            >
                                <LogOutIcon />
                                {translate('identity.user_menu.sign_out')}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </nav>
    );
}
