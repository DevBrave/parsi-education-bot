# Project architecture file index

Generated from the working tree on 2026-11-03. Excludes installed dependencies, runtime data, secrets, SQL dumps, lockfile contents, and generated assets. This is a navigation index, not a claim of line-by-line verification.

## .editorconfig

## .env.example

## .gitattributes

## .github/dependabot.yml

## .github/workflows/tests.yml

## .gitignore

## .npmrc

## app/Actions/Fortify/CreateNewUser.php
- class CreateNewUser
- function create(

## app/Actions/Fortify/ResetUserPassword.php
- class ResetUserPassword
- function reset(

## app/Auth/TelegramGuard.php
- class TelegramGuard
- function __construct(
- function user(
- function validate(

## app/Concerns/PasswordValidationRules.php
- trait PasswordValidationRules
- function passwordRules(
- function currentPasswordRules(

## app/Concerns/ProfileValidationRules.php
- trait ProfileValidationRules
- function profileRules(
- function nameRules(
- function emailRules(

## app/Console/Commands/SetAdminCredentialsCommand.php
- class SetAdminCredentialsCommand
- function handle(

## app/Console/Commands/TelegramSetWebhook.php
- class TelegramSetWebhook
- function handle(

## app/helpers.php
- function bot_log(
- function log_rbac(

## app/Http/Controllers/Admin/AcademicYearAdminController.php
- class AcademicYearAdminController
- function index(
- function store(
- function edit(
- function update(
- function setCurrent(
- function destroy(

## app/Http/Controllers/Admin/AdminAuthController.php
- class AdminAuthController
- function showLoginForm(
- function login(
- function logout(

## app/Http/Controllers/Admin/AdminDashboardController.php
- class AdminDashboardController
- function index(
- function getUserStats(
- function getCourseStats(
- function getYearStats(
- function getHeavyResourceStats(
- function getChartData(
- function formatBytes(

## app/Http/Controllers/Admin/CourseAdminController.php
- class CourseAdminController
- function index(
- function store(
- function edit(
- function update(
- function destroy(

## app/Http/Controllers/Admin/LogAdminController.php
- class LogAdminController
- function index(
- function poll(
- function stream(
- function download(
- function clear(
- function resolveLogPath(
- function readLastLines(
- function parseLogLine(
- function formatBytes(

## app/Http/Controllers/Admin/ProgramAdminController.php
- class ProgramAdminController
- function index(
- function store(
- function edit(
- function update(
- function destroy(

## app/Http/Controllers/Admin/ReportAdminController.php
- class ReportAdminController
- function index(
- function show(
- function resolve(
- function dismiss(
- function destroyResource(

## app/Http/Controllers/Admin/ResourceAdminController.php
- class ResourceAdminController
- function index(
- function create(
- function store(
- function edit(
- function update(
- function destroyFile(
- function destroy(
- function downloadFile(
- function syncDrive(

## app/Http/Controllers/Admin/ResourceTypeAdminController.php
- class ResourceTypeAdminController
- function index(
- function store(
- function edit(
- function update(
- function destroy(

## app/Http/Controllers/Admin/UserAdminController.php
- class UserAdminController
- function index(
- function edit(
- function update(

## app/Http/Controllers/Controller.php
- abstract class Controller

## app/Http/Controllers/Settings/ProfileController.php
- class ProfileController
- function edit(
- function update(
- function destroy(

## app/Http/Controllers/Settings/SecurityController.php
- class SecurityController
- function edit(
- function update(

## app/Http/Controllers/TelegramWebhookController.php
- class TelegramWebhookController
- function __construct(
- function handle(
- function handleMessage(
- function handleCallbackQuery(

## app/Http/Middleware/EnsureTelegramTokenIsValid.php
- class EnsureTelegramTokenIsValid
- function handle(

## app/Http/Middleware/HandleAppearance.php
- class HandleAppearance
- function handle(

## app/Http/Middleware/HandleInertiaRequests.php
- class HandleInertiaRequests
- function version(
- function share(

## app/Http/Middleware/IsAdmin.php
- class IsAdmin
- function handle(

## app/Http/Requests/Settings/PasswordUpdateRequest.php
- class PasswordUpdateRequest
- function rules(

## app/Http/Requests/Settings/ProfileDeleteRequest.php
- class ProfileDeleteRequest
- function rules(

## app/Http/Requests/Settings/ProfileUpdateRequest.php
- class ProfileUpdateRequest
- function rules(

## app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php
- class TwoFactorAuthenticationRequest
- function rules(

## app/Jobs/FinalizeSubmissionJob.php
- class FinalizeSubmissionJob
- function __construct(
- function handle(

## app/Jobs/ProcessTelegramUpdate.php
- class ProcessTelegramUpdate
- function __construct(
- function handle(

## app/Jobs/UploadResourceFileJob.php
- class UploadResourceFileJob
- function __construct(
- function handle(
- function failed(
- function uniqueId(

## app/Models/AcademicYear.php
- class AcademicYear
- function casts(
- function resources(

## app/Models/Course.php
- class Course
- function booted(
- function clearCourseCaches(
- function programs(
- function tracks(
- function resources(
- function casts(
- function getYearLabelAttribute(

## app/Models/Professor.php
- class Professor
- function resources(

## app/Models/Program.php
- class Program
- function booted(
- function courses(
- function tracks(
- function users(
- function defaultYears(
- function casts(

## app/Models/Resource.php
- class Resource
- function booted(
- function course(
- function resourceType(
- function academicYear(
- function submitter(
- function reviewer(
- function professors(
- function files(
- function isCommunitySubmission(
- function isOfficial(
- function buildTelegramCaption(

## app/Models/ResourceFile.php
- class ResourceFile
- function resource(
- function reports(
- function getFormattedSizeAttribute(

## app/Models/ResourceReport.php
- class ResourceReport
- function casts(
- function user(
- function resolver(
- function resource(
- function resourceFile(
- function scopePending(
- function scopeResolved(
- function scopeDismissed(
- function scopeForUser(
- function getReasonLabelAttribute(

## app/Models/ResourceType.php
- class ResourceType
- function booted(
- function casts(
- function resources(

## app/Models/Track.php
- class Track
- function booted(
- function program(
- function courses(
- function users(
- function casts(

## app/Models/User.php
- class User
- function casts(
- function booted(
- function program(
- function track(
- function submittedResources(
- function reviewedResources(
- function isAdmin(
- function isModerator(
- function isMajorAdmin(
- function isEditor(
- function isViewer(
- function isProfessor(
- function isStudent(
- function isStaff(
- function canManageUsers(
- function canManageCatalog(
- function canViewReports(
- function canViewLogs(
- function canManageReport(
- function canCreateResources(
- function canEditResource(
- function canDeleteResource(
- function canApproveResource(
- function canMutateResource(
- function isResourceInMyMajor(
- function getRoleLabelAttribute(

## app/Providers/AppServiceProvider.php
- class AppServiceProvider
- function register(
- function boot(
- function configureDefaults(

## app/Providers/FortifyServiceProvider.php
- class FortifyServiceProvider
- function register(
- function boot(
- function configureActions(
- function configureViews(
- function configureRateLimiting(

## app/Services/FileSecurityService.php
- class FileSecurityService
- function allowedMimesString(
- function validateUpload(
- function verifyFileContent(
- function hasDangerousDoubleExtension(
- function sanitizeFileName(
- function isClamAvAvailable(
- function scanWithClamAv(

## app/Services/GoogleDriveService.php
- class GoogleDriveService
- function __construct(
- function initialize(
- function createClient(
- function isConfigured(
- function getOrCreateFolder(
- function uploadFile(
- function deleteFile(
- function log(

## app/Services/ResourceModerationService.php
- class ResourceModerationService
- function __construct(
- function approve(
- function reject(
- function notifySubmitter(
- function syncDrive(
- function sendToStorageChannel(

## app/Services/ResourceUploadService.php
- class ResourceUploadService
- function __construct(
- function sync(
- function upload(
- function uploadToTelegram(
- function uploadToGoogleDrive(

## app/Services/TelegramService.php
- class TelegramService
- function __construct(
- function client(
- function sendMessage(
- function setWebhook(
- function getWebhookInfo(
- function editMessageText(
- function editMessageCaption(
- function answerCallbackQuery(
- function answerInlineQuery(
- function uploadDocument(
- function sendDocument(
- function sendPhoto(
- function sendMediaGroup(
- function getMe(
- function getFile(
- function getFileDownloadUrl(
- function deleteMessage(
- function log(

## app/Telegram/Handlers/CallbackQueryHandler.php
- class CallbackQueryHandler
- function __construct(
- function handle(
- function handleSelectSubmitCourseYear(
- function handleSelectSubmitCourseType(
- function handleSubmitResourcePrompt(
- function handleSelectProgram(
- function handleSelectTrack(
- function handleSelectSubmitTrack(
- function handleSelectCourseYear(
- function handleSelectCourse(
- function handleSelectResourceType(
- function handleSendResourceDirect(
- function handleSelectCourseDirect(
- function handleReportPromptDirect(
- function handleSendResource(
- function sendGoogleDriveDownloadOption(
- function handleReportPrompt(
- function handleReportSubmit(
- function handleAdminApprove(
- function handleAdminReject(
- function handleFinishUpload(

## app/Telegram/Handlers/FileUploadHandler.php
- class FileUploadHandler
- function __construct(
- function handle(
- function notifyAdminsForResource(
- function notifyAdmins(
- function formatBytes(
- function extractFileInfo(

## app/Telegram/Handlers/InlineQueryHandler.php
- class InlineQueryHandler
- function __construct(
- function handle(
- function isPhotoFile(
- function convertToArticleFallback(

## app/Telegram/Handlers/MessageHandler.php
- class MessageHandler
- function __construct(
- function handle(
- function sendMainMenu(
- function sendSettingsMenu(
- function sendSubmitPrompt(
- function sendEducationalFiles(
- function sendProgrammeSelector(
- function sendSearchPrompt(
- function executeSearch(
- function sendTutorialsMenu(
- function sendStudentGroupsMenu(
- function sendReportMenu(
- function sendContactMenu(
- function handleReportMessageSubmit(
- function handleContactMessageSubmit(
- function sendAboutUsMenu(

## app/Telegram/Handlers/OnboardingHandler.php
- class OnboardingHandler
- function __construct(
- function handle(
- function promptLanguage(
- function promptDegreeLevel(
- function promptProgramsByDegree(
- function promptProgram(
- function handleLanguageSelection(
- function handleDegreeSelection(
- function handleBackToDegrees(
- function handleProgramSelection(
- function handleTrackSelection(
- function finishOnboarding(

## app/Telegram/Keyboards/MainMenuKeyboard.php
- class MainMenuKeyboard
- function build(

## app/Telegram/Keyboards/OnboardingKeyboard.php
- class OnboardingKeyboard
- function languages(
- function degrees(
- function programsByDegree(
- function tracks(
- function programmes(

## app/Telegram/Keyboards/ProgramMenuKeyboard.php
- class ProgramMenuKeyboard
- function educationHub(
- function programmes(
- function degrees(
- function programsByDegree(
- function tracks(
- function getProgramYears(
- function years(
- function courses(
- function submitCourses(
- function getActiveResourceTypes(
- function submitResourceTypes(
- function resourceTypes(
- function resources(
- function settingsMenu(
- function simpleYesOrNo(
- function searchResults(
- function searchPrompt(
- function reportReasons(
- function contactMenu(
- function reportMenu(
- function tutorialsMenu(
- function aboutUsMenu(
- function aboutMembersMenu(

## app/Telegram/Middleware/CheckUserOnboarding.php
- class CheckUserOnboarding
- function __construct(
- function handle(

## app/Telegram/Middleware/TelegramRateLimiter.php
- class TelegramRateLimiter
- function __construct(
- function handle(

## app/Telegram/TelegramUpdateDispatcher.php
- class TelegramUpdateDispatcher
- function __construct(
- function dispatch(
- function resolveUser(
- function handleChosenInlineResult(

## artisan

## bootstrap/app.php

## bootstrap/providers.php

## components.json

## composer.json

## config/app.php

## config/auth.php

## config/cache.php

## config/database.php

## config/filesystems.php

## config/fortify.php

## config/google.php

## config/inertia.php

## config/logging.php

## config/mail.php

## config/queue.php

## config/services.php

## config/session.php

## config/telegram.php

## database/.gitignore

## database/factories/UserFactory.php
- class UserFactory
- function definition(
- function unverified(
- function withTwoFactor(

## database/migrations/0001_01_01_000000_create_users_table.php
- function up(
- function down(

## database/migrations/0001_01_01_000001_create_cache_table.php
- function up(
- function down(

## database/migrations/0001_01_01_000002_create_jobs_table.php
- function up(
- function down(

## database/migrations/2024_01_01_000000_create_passkeys_table.php
- function up(
- function down(

## database/migrations/2026_09_01_185144_create_programs_table.php
- function up(
- function down(

## database/migrations/2026_09_01_185654_create_academic_years_table.php
- function up(
- function down(

## database/migrations/2026_09_01_190011_create_professors_table.php
- function up(
- function down(

## database/migrations/2026_09_01_190201_create_resource_types_table.php
- function up(
- function down(

## database/migrations/2026_09_01_190440_create_courses_table.php
- function up(
- function down(

## database/migrations/2026_09_01_192355_create_course_program_table.php
- function up(
- function down(

## database/migrations/2026_09_02_114105_create_resources_table.php
- function up(
- function down(

## database/migrations/2026_09_02_154237_create_professor_resource_table.php
- function up(
- function down(

## database/migrations/2026_09_02_160034_create_resource_files_table.php
- function up(
- function down(

## database/migrations/2026_10_08_230433_add_program_id_to_users_table.php
- function up(
- function down(

## database/migrations/2026_10_15_021500_add_year_to_courses_table.php
- function up(
- function down(

## database/migrations/2026_10_15_033000_add_local_path_to_resource_files_table.php
- function up(
- function down(

## database/migrations/2026_10_15_043000_create_resource_reports_table.php
- function up(
- function down(

## database/migrations/2026_10_18_181500_add_telegram_message_id_to_resource_files_table.php
- function up(
- function down(

## database/migrations/2026_10_18_194500_add_downloads_and_inline_shares_to_resources_table.php
- function up(
- function down(

## database/migrations/2026_10_19_122526_add_auth_columns_to_users_table.php
- function up(
- function down(

## database/migrations/2026_10_27_200000_create_tracks_and_course_track_tables.php
- function up(
- function down(

## database/seeders/AcademicYearSeeder.php
- class AcademicYearSeeder
- function run(

## database/seeders/CatalogueSeeder.php
- class CatalogueSeeder
- function run(

## database/seeders/CompleteCatalogSeeder.php
- class CompleteCatalogSeeder
- function run(

## database/seeders/CourseYearSeeder.php
- class CourseYearSeeder
- function run(

## database/seeders/DatabaseSeeder.php
- class DatabaseSeeder
- function run(

## database/seeders/MasterTrackSeeder.php
- class MasterTrackSeeder
- function run(

## database/seeders/ResourceTypeSeeder.php
- class ResourceTypeSeeder
- function run(

## database/seeders/TestResourceSeeder.php
- class TestResourceSeeder
- function run(

## doc/social_media_features_brief.md

## doc/system_enhancements_and_roadmap.md

## lang/en/auth.php

## lang/en/bot.php

## lang/en/pagination.php

## lang/en/passwords.php

## lang/en/validation.php

## lang/fa/bot.php

## lang/it/bot.php

## package.json

## phpstan.neon

## phpunit.xml

## pint.json

## pnpm-workspace.yaml

## public/.htaccess

## public/apple-touch-icon.png

## public/css/admin.css

## public/favicon.ico

## public/favicon.svg

## public/index.php

## public/ParsiLogo.jpg

## public/robots.txt

## resources/css/app.css

## resources/js/app.ts

## resources/js/components/AlertError.vue

## resources/js/components/AppContent.vue

## resources/js/components/AppearanceTabs.vue

## resources/js/components/AppHeader.vue

## resources/js/components/AppLogo.vue

## resources/js/components/AppLogoIcon.vue

## resources/js/components/AppShell.vue

## resources/js/components/AppSidebar.vue

## resources/js/components/AppSidebarHeader.vue

## resources/js/components/Breadcrumbs.vue

## resources/js/components/DeleteUser.vue

## resources/js/components/Heading.vue

## resources/js/components/InputError.vue

## resources/js/components/ManagePasskeys.vue

## resources/js/components/ManageTwoFactor.vue

## resources/js/components/NavFooter.vue

## resources/js/components/NavMain.vue

## resources/js/components/NavUser.vue

## resources/js/components/PasskeyItem.vue

## resources/js/components/PasskeyRegister.vue

## resources/js/components/PasskeyVerify.vue

## resources/js/components/PasswordInput.vue

## resources/js/components/PlaceholderPattern.vue

## resources/js/components/TextLink.vue

## resources/js/components/TwoFactorRecoveryCodes.vue

## resources/js/components/TwoFactorSetupModal.vue

## resources/js/components/ui/alert/Alert.vue

## resources/js/components/ui/alert/AlertDescription.vue

## resources/js/components/ui/alert/AlertTitle.vue

## resources/js/components/ui/alert/index.ts

## resources/js/components/ui/avatar/Avatar.vue

## resources/js/components/ui/avatar/AvatarFallback.vue

## resources/js/components/ui/avatar/AvatarImage.vue

## resources/js/components/ui/avatar/index.ts

## resources/js/components/ui/badge/Badge.vue

## resources/js/components/ui/badge/index.ts

## resources/js/components/ui/breadcrumb/Breadcrumb.vue

## resources/js/components/ui/breadcrumb/BreadcrumbEllipsis.vue

## resources/js/components/ui/breadcrumb/BreadcrumbItem.vue

## resources/js/components/ui/breadcrumb/BreadcrumbLink.vue

## resources/js/components/ui/breadcrumb/BreadcrumbList.vue

## resources/js/components/ui/breadcrumb/BreadcrumbPage.vue

## resources/js/components/ui/breadcrumb/BreadcrumbSeparator.vue

## resources/js/components/ui/breadcrumb/index.ts

## resources/js/components/ui/button/Button.vue
- interface Props

## resources/js/components/ui/button/index.ts

## resources/js/components/ui/card/Card.vue

## resources/js/components/ui/card/CardAction.vue

## resources/js/components/ui/card/CardContent.vue

## resources/js/components/ui/card/CardDescription.vue

## resources/js/components/ui/card/CardFooter.vue

## resources/js/components/ui/card/CardHeader.vue

## resources/js/components/ui/card/CardTitle.vue

## resources/js/components/ui/card/index.ts

## resources/js/components/ui/checkbox/Checkbox.vue

## resources/js/components/ui/checkbox/index.ts

## resources/js/components/ui/collapsible/Collapsible.vue

## resources/js/components/ui/collapsible/CollapsibleContent.vue

## resources/js/components/ui/collapsible/CollapsibleTrigger.vue

## resources/js/components/ui/collapsible/index.ts

## resources/js/components/ui/dialog/Dialog.vue

## resources/js/components/ui/dialog/DialogClose.vue

## resources/js/components/ui/dialog/DialogContent.vue

## resources/js/components/ui/dialog/DialogDescription.vue

## resources/js/components/ui/dialog/DialogFooter.vue

## resources/js/components/ui/dialog/DialogHeader.vue

## resources/js/components/ui/dialog/DialogOverlay.vue

## resources/js/components/ui/dialog/DialogScrollContent.vue

## resources/js/components/ui/dialog/DialogTitle.vue

## resources/js/components/ui/dialog/DialogTrigger.vue

## resources/js/components/ui/dialog/index.ts

## resources/js/components/ui/dropdown-menu/DropdownMenu.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuCheckboxItem.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuContent.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuGroup.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuItem.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuLabel.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuRadioGroup.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuRadioItem.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuSeparator.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuShortcut.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuSub.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuSubContent.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuSubTrigger.vue

## resources/js/components/ui/dropdown-menu/DropdownMenuTrigger.vue

## resources/js/components/ui/dropdown-menu/index.ts

## resources/js/components/ui/input/index.ts

## resources/js/components/ui/input/Input.vue

## resources/js/components/ui/input-otp/index.ts

## resources/js/components/ui/input-otp/InputOTP.vue

## resources/js/components/ui/input-otp/InputOTPGroup.vue

## resources/js/components/ui/input-otp/InputOTPSeparator.vue

## resources/js/components/ui/input-otp/InputOTPSlot.vue

## resources/js/components/ui/label/index.ts

## resources/js/components/ui/label/Label.vue

## resources/js/components/ui/navigation-menu/index.ts

## resources/js/components/ui/navigation-menu/NavigationMenu.vue

## resources/js/components/ui/navigation-menu/NavigationMenuContent.vue

## resources/js/components/ui/navigation-menu/NavigationMenuIndicator.vue

## resources/js/components/ui/navigation-menu/NavigationMenuItem.vue

## resources/js/components/ui/navigation-menu/NavigationMenuLink.vue

## resources/js/components/ui/navigation-menu/NavigationMenuList.vue

## resources/js/components/ui/navigation-menu/NavigationMenuTrigger.vue

## resources/js/components/ui/navigation-menu/NavigationMenuViewport.vue

## resources/js/components/ui/select/index.ts

## resources/js/components/ui/select/Select.vue

## resources/js/components/ui/select/SelectContent.vue

## resources/js/components/ui/select/SelectGroup.vue

## resources/js/components/ui/select/SelectItem.vue

## resources/js/components/ui/select/SelectItemText.vue

## resources/js/components/ui/select/SelectLabel.vue

## resources/js/components/ui/select/SelectScrollDownButton.vue

## resources/js/components/ui/select/SelectScrollUpButton.vue

## resources/js/components/ui/select/SelectSeparator.vue

## resources/js/components/ui/select/SelectTrigger.vue

## resources/js/components/ui/select/SelectValue.vue

## resources/js/components/ui/separator/index.ts

## resources/js/components/ui/separator/Separator.vue

## resources/js/components/ui/sheet/index.ts

## resources/js/components/ui/sheet/Sheet.vue

## resources/js/components/ui/sheet/SheetClose.vue

## resources/js/components/ui/sheet/SheetContent.vue
- interface SheetContentProps

## resources/js/components/ui/sheet/SheetDescription.vue

## resources/js/components/ui/sheet/SheetFooter.vue

## resources/js/components/ui/sheet/SheetHeader.vue

## resources/js/components/ui/sheet/SheetOverlay.vue

## resources/js/components/ui/sheet/SheetTitle.vue

## resources/js/components/ui/sheet/SheetTrigger.vue

## resources/js/components/ui/sidebar/index.ts

## resources/js/components/ui/sidebar/Sidebar.vue

## resources/js/components/ui/sidebar/SidebarContent.vue

## resources/js/components/ui/sidebar/SidebarFooter.vue

## resources/js/components/ui/sidebar/SidebarGroup.vue

## resources/js/components/ui/sidebar/SidebarGroupAction.vue

## resources/js/components/ui/sidebar/SidebarGroupContent.vue

## resources/js/components/ui/sidebar/SidebarGroupLabel.vue

## resources/js/components/ui/sidebar/SidebarHeader.vue

## resources/js/components/ui/sidebar/SidebarInput.vue

## resources/js/components/ui/sidebar/SidebarInset.vue

## resources/js/components/ui/sidebar/SidebarMenu.vue

## resources/js/components/ui/sidebar/SidebarMenuAction.vue

## resources/js/components/ui/sidebar/SidebarMenuBadge.vue

## resources/js/components/ui/sidebar/SidebarMenuButton.vue

## resources/js/components/ui/sidebar/SidebarMenuButtonChild.vue

## resources/js/components/ui/sidebar/SidebarMenuItem.vue

## resources/js/components/ui/sidebar/SidebarMenuSkeleton.vue

## resources/js/components/ui/sidebar/SidebarMenuSub.vue

## resources/js/components/ui/sidebar/SidebarMenuSubButton.vue

## resources/js/components/ui/sidebar/SidebarMenuSubItem.vue

## resources/js/components/ui/sidebar/SidebarProvider.vue
- function setOpen(
- function setOpenMobile(
- function toggleSidebar(

## resources/js/components/ui/sidebar/SidebarRail.vue

## resources/js/components/ui/sidebar/SidebarSeparator.vue

## resources/js/components/ui/sidebar/SidebarTrigger.vue

## resources/js/components/ui/sidebar/utils.ts

## resources/js/components/ui/skeleton/index.ts

## resources/js/components/ui/skeleton/Skeleton.vue
- interface SkeletonProps

## resources/js/components/ui/sonner/index.ts

## resources/js/components/ui/sonner/Sonner.vue

## resources/js/components/ui/spinner/index.ts

## resources/js/components/ui/spinner/Spinner.vue

## resources/js/components/ui/tooltip/index.ts

## resources/js/components/ui/tooltip/Tooltip.vue

## resources/js/components/ui/tooltip/TooltipContent.vue

## resources/js/components/ui/tooltip/TooltipProvider.vue

## resources/js/components/ui/tooltip/TooltipTrigger.vue

## resources/js/components/UserInfo.vue

## resources/js/components/UserMenuContent.vue

## resources/js/composables/useAppearance.ts
- function updateTheme(
- function initializeTheme(
- function useAppearance(
- function updateAppearance(

## resources/js/composables/useCurrentUrl.ts
- function useCurrentUrl(
- function isCurrentUrl(
- function isCurrentOrParentUrl(
- function whenCurrentUrl(

## resources/js/composables/useInitials.ts
- function getInitial(
- function getInitials(
- function useInitials(

## resources/js/composables/useTwoFactorAuth.ts

## resources/js/layouts/app/AppHeaderLayout.vue

## resources/js/layouts/app/AppSidebarLayout.vue

## resources/js/layouts/AppLayout.vue

## resources/js/layouts/auth/AuthCardLayout.vue

## resources/js/layouts/auth/AuthSimpleLayout.vue

## resources/js/layouts/auth/AuthSplitLayout.vue

## resources/js/layouts/AuthLayout.vue

## resources/js/layouts/settings/Layout.vue

## resources/js/lib/flashToast.ts
- function initializeFlashToast(

## resources/js/lib/utils.ts
- function cn(
- function toUrl(

## resources/js/pages/auth/ConfirmPassword.vue

## resources/js/pages/auth/ForgotPassword.vue

## resources/js/pages/auth/Login.vue

## resources/js/pages/auth/Register.vue

## resources/js/pages/auth/ResetPassword.vue

## resources/js/pages/auth/TwoFactorChallenge.vue

## resources/js/pages/auth/VerifyEmail.vue

## resources/js/pages/Dashboard.vue

## resources/js/pages/settings/Appearance.vue

## resources/js/pages/settings/Profile.vue

## resources/js/pages/settings/Security.vue

## resources/js/pages/Welcome.vue

## resources/js/types/auth.ts

## resources/js/types/global.d.ts
- interface ImportMetaEnv
- interface ImportMeta
- interface ComponentCustomProperties

## resources/js/types/index.ts

## resources/js/types/navigation.ts

## resources/js/types/ui.ts

## resources/js/types/vue-shims.d.ts

## resources/views/admin/academic-years/edit.blade.php

## resources/views/admin/academic-years/index.blade.php

## resources/views/admin/auth/login.blade.php

## resources/views/admin/courses/edit.blade.php
- function syncSpecializationTracks(
- function filterProgramsByDegreeLevel(

## resources/views/admin/courses/index.blade.php
- function syncSpecializationTracks(
- function filterProgramsByDegreeLevel(

## resources/views/admin/dashboard.blade.php

## resources/views/admin/layout.blade.php

## resources/views/admin/logs/index.blade.php
- function scrollToBottom(
- function fetchLogs(
- function startPolling(
- function stopPolling(
- function appendLogLine(
- function applyClientFilters(
- function shouldShowRow(
- function updateLineCount(
- function escapeHtml(

## resources/views/admin/partials/pagination.blade.php

## resources/views/admin/programs/edit.blade.php
- function updateTracksVisibility(

## resources/views/admin/programs/index.blade.php
- function updateTracksVisibility(

## resources/views/admin/reports/index.blade.php

## resources/views/admin/reports/show.blade.php

## resources/views/admin/resources/create.blade.php
- function updateFileName(

## resources/views/admin/resources/edit.blade.php
- function updateFileNames(

## resources/views/admin/resources/index.blade.php

## resources/views/admin/resource-types/edit.blade.php

## resources/views/admin/resource-types/index.blade.php

## resources/views/admin/users/edit.blade.php
- function updateProgramVisibility(

## resources/views/admin/users/index.blade.php

## resources/views/app.blade.php

## resources/views/debug/telegram.blade.php

## routes/api.php

## routes/console.php

## routes/settings.php

## routes/web.php

## tests/Feature/AdminAuthTest.php
- class AdminAuthTest
- function test_admin_login_screen_can_be_rendered(
- function test_admin_can_authenticate_with_valid_credentials(
- function test_admin_can_authenticate_with_username(
- function test_admin_cannot_authenticate_with_invalid_password(
- function test_non_admin_user_is_denied_access(
- function test_admin_can_logout(
- function test_unauthenticated_user_accessing_admin_is_redirected_to_admin_login(

## tests/Feature/AdminTracksFeatureTest.php
- class AdminTracksFeatureTest
- function setUp(
- function test_courses_index_page_renders_degree_level_and_tracks_sections(
- function test_courses_store_saves_selected_tracks_to_database(
- function test_courses_update_syncs_tracks_in_database(
- function test_programs_index_page_renders_degree_level_radios_and_tracks_container(
- function test_programs_store_saves_master_tracks_to_database(
- function test_programs_update_syncs_tracks_correctly(
- function test_programs_update_to_bachelor_clears_tracks(

## tests/Feature/Auth/AuthenticationTest.php
- class AuthenticationTest
- function test_login_screen_can_be_rendered(
- function test_users_can_authenticate_using_the_login_screen(
- function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(
- function test_users_can_not_authenticate_with_invalid_password(
- function test_users_can_logout(
- function test_users_are_rate_limited(

## tests/Feature/Auth/EmailVerificationTest.php
- class EmailVerificationTest
- function setUp(
- function test_email_verification_screen_can_be_rendered(
- function test_email_can_be_verified(
- function test_email_is_not_verified_with_invalid_hash(
- function test_email_is_not_verified_with_invalid_user_id(
- function test_verified_user_is_redirected_to_dashboard_from_verification_prompt(
- function test_already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again(

## tests/Feature/Auth/PasswordConfirmationTest.php
- class PasswordConfirmationTest
- function test_confirm_password_screen_can_be_rendered(
- function test_password_confirmation_requires_authentication(

## tests/Feature/Auth/PasswordResetTest.php
- class PasswordResetTest
- function setUp(
- function test_reset_password_link_screen_can_be_rendered(
- function test_reset_password_link_can_be_requested(
- function test_reset_password_screen_can_be_rendered(
- function test_password_can_be_reset_with_valid_token(
- function test_password_cannot_be_reset_with_invalid_token(

## tests/Feature/Auth/RegistrationTest.php
- class RegistrationTest
- function setUp(
- function test_registration_screen_can_be_rendered(
- function test_new_users_can_register(

## tests/Feature/Auth/TwoFactorChallengeTest.php
- class TwoFactorChallengeTest
- function setUp(
- function test_two_factor_challenge_redirects_to_login_when_not_authenticated(
- function test_two_factor_challenge_can_be_rendered(

## tests/Feature/Auth/VerificationNotificationTest.php
- class VerificationNotificationTest
- function setUp(
- function test_sends_verification_notification(
- function test_does_not_send_verification_notification_if_email_is_verified(

## tests/Feature/DashboardTest.php
- class DashboardTest
- function test_guests_are_redirected_to_the_login_page(
- function test_authenticated_admin_can_visit_the_dashboard(
- function test_dashboard_caches_heavy_metrics_and_supports_refresh(

## tests/Feature/ExampleTest.php
- class ExampleTest
- function test_returns_a_successful_response(

## tests/Feature/LogAdminTest.php
- class LogAdminTest
- function setUp(
- function test_guest_is_redirected_to_login(
- function test_student_and_viewer_are_forbidden(
- function test_admin_and_moderator_can_view_logs_dashboard(
- function test_can_switch_channels(
- function test_can_download_log_file(
- function test_only_super_admin_can_purge_log_file(
- function test_parse_log_line_extracts_structured_data(
- function test_admin_can_poll_new_logs(
- function test_student_cannot_poll_logs(

## tests/Feature/MasterTrackTest.php
- class MasterTrackTest
- function test_master_track_seeder_creates_msc_program_and_seven_tracks(
- function test_program_with_tracks_shows_track_menu_on_selection(
- function test_selecting_track_updates_user_and_shows_years(
- function test_course_filtering_respects_tracks_and_common_core_courses(
- function test_admin_can_assign_tracks_to_course(
- function invokeHandler(

## tests/Feature/OnboardingMiddlewareTest.php
- class OnboardingMiddlewareTest
- function setUp(
- function test_new_user_without_language_is_prompted_for_language(
- function test_user_selecting_language_is_prompted_for_degree_programme(
- function test_user_selecting_programme_completes_onboarding(
- function test_fully_onboarded_user_bypasses_onboarding(

## tests/Feature/ReportAdminTest.php
- class ReportAdminTest
- function setUp(
- function test_admin_can_view_reports_index_and_details(
- function test_moderator_can_resolve_and_dismiss_reports(
- function test_professor_scoped_to_assigned_major_reports(
- function test_viewer_has_read_only_access(
- function test_telegram_student_reporting_flow(
- function test_telegram_contact_report_menu_and_feedback_flow(
- function test_destroy_resource_deletes_only_reported_file_when_other_files_exist(

## tests/Feature/ResourceAdminTest.php
- class ResourceAdminTest
- function setUp(
- function test_guests_cannot_access_resources_admin(
- function test_student_cannot_access_resources_admin(
- function test_admin_can_view_resources_list_with_platform_statuses(
- function test_admin_can_filter_resources_by_platform_sync(
- function test_admin_can_view_edit_resource_page(
- function test_admin_can_update_resource(
- function test_admin_can_delete_resource(
- function test_admin_can_delete_individual_file_and_it_purges_from_telegram_and_drive(
- function test_admin_can_create_resource_with_status(
- function test_admin_editing_resource_and_adding_file_does_not_duplicate_admin_file(

## tests/Feature/SecurityFuzzTest.php
- class SecurityFuzzTest
- function setUp(
- function test_webhook_rejects_missing_secret_token_when_configured(
- function test_webhook_rejects_spoofed_secret_token(
- function test_webhook_accepts_valid_secret_token(
- function test_webhook_payload_fuzzing_resilience(
- function test_search_handles_sqli_and_xss_injection_attempts(
- function test_admin_login_is_rate_limited_against_brute_force(
- function test_rate_limiter_class_fuzzing_and_thresholds(
- function test_burst_fuzz_production_queued_200_requests_within_30_seconds(
- function invokeHandler(

## tests/Feature/Settings/ProfileUpdateTest.php
- class ProfileUpdateTest
- function test_profile_page_is_displayed(
- function test_profile_information_can_be_updated(
- function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(
- function test_user_can_delete_their_account(
- function test_correct_password_must_be_provided_to_delete_account(

## tests/Feature/Settings/SecurityTest.php
- class SecurityTest
- function test_security_page_is_displayed(
- function test_security_page_requires_password_confirmation_when_enabled(
- function test_security_page_renders_without_two_factor_when_feature_is_disabled(
- function test_password_can_be_updated(
- function test_correct_password_must_be_provided_to_update_password(

## tests/Feature/TelegramDocumentTest.php
- class TelegramDocumentTest
- function test_document_is_sent_using_the_telegram_file_id(
- function test_telegram_failure_is_logged_and_returned(
- function test_send_resource_with_drive_file_id_fallback_when_telegram_file_id_missing(
- function test_send_resource_with_telegram_file_id_sends_document_directly(
- function test_album_upload_updates_resource_description_when_caption_arrives_on_subsequent_file(
- function test_send_album_of_documents_with_caption_to_telegram(
- function test_admin_approval_and_rejection_via_telegram_notifies_student_in_their_language(
- function test_submission_wizard_shows_resource_types_and_saves_selection_to_cache(
- function test_submission_multi_file_updates_single_feedback_message(

## tests/Feature/TelegramGroupSearchTest.php
- class TelegramGroupSearchTest
- function setUp(
- function test_group_mention_triggers_search_without_back_navigation(
- function test_inline_query_returns_courses_and_resources(
- function test_chosen_inline_result_increments_downloads_and_inline_shares_count(

## tests/Feature/TelegramMenuTest.php
- class TelegramMenuTest
- function test_start_shows_a_persistent_reply_keyboard_and_escapes_the_name(
- function test_back_to_main_closes_the_inline_menu_and_restores_the_reply_keyboard(
- function test_callback_without_a_chat_message_is_acknowledged_without_editing(
- function test_persian_user_receives_persian_menu(
- function test_italian_user_receives_italian_menu(
- function test_search_prompt_and_execution_returns_matching_courses_and_resources(
- function test_direct_search_command_executes_immediately(
- function test_program_menu_keyboard_years_and_caching(
- function test_about_us_and_members_menu(
- function test_tutorials_and_report_menu_actions(
- function test_student_groups_menu_action(
- function invokeHandler(

## tests/Feature/UserRolesTest.php
- class UserRolesTest
- function setUp(
- function test_admin_can_access_user_management(
- function test_non_admin_cannot_access_user_management(
- function test_viewer_can_see_resources_list_but_cannot_create_or_delete(
- function test_editor_can_modify_courses_and_edit_resources(
- function test_professor_can_view_all_resources_but_only_mutate_their_major(
- function test_student_is_denied_access_to_admin_panel(
- function test_admin_can_update_user_role_and_major(
- function test_moderator_can_edit_all_majors_but_cannot_manage_users(
- function test_major_admin_is_restricted_to_their_major(

## tests/TestCase.php
- abstract class TestCase
- function skipUnlessFortifyHas(

## tests/Unit/ExampleTest.php
- class ExampleTest
- function test_that_true_is_true(

## tests/Unit/GoogleDriveServiceTest.php
- class GoogleDriveServiceTest
- function test_oauth_failure_does_not_fall_back_to_service_account(
- function __construct(
- function createClient(
- function test_constructor_does_not_initialize_google_client(
- function initialize(
- function test_is_configured_returns_false_when_unconfigured(
- function test_upload_file_returns_null_when_unconfigured(
- function test_upload_file_returns_null_for_nonexistent_file(

## tests/Unit/ResourceCaptionTest.php
- class ResourceCaptionTest
- function test_build_telegram_caption_without_description(
- function test_build_telegram_caption_with_description(
- function test_build_telegram_caption_with_suffix_and_middle_section(

## tests/Unit/ResourceUploadStatusTest.php
- class ResourceUploadStatusTest
- function languages(
- function test_drive_only_upload_posts_download_link_to_telegram_channel(
- function test_telegram_status_is_saved_before_drive_starts(

## tests/Unit/TelegramUploadTest.php
- class TelegramUploadTest
- function test_small_document_uses_cloud_endpoint_and_short_timeout(

## tsconfig.json

## vite.config.ts

