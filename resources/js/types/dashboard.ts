export type TrafficLightStatus = 'green' | 'yellow' | 'red';

export interface HourAccumulation {
    jornadaHours: number;
    externalHours: number;
    totalHours: number;
    quota: number;
    percentage: number;
    status: TrafficLightStatus;
}

export interface StudentProgress {
    studentId: number;
    studentName: string;
    hours: HourAccumulation;
    sectionName?: string;
    gradeName?: string;
}

export interface SectionProgress {
    sectionId: number;
    sectionName: string;
    gradeName: string;
    averageProgress: number;
    studentCount: number;
    students: StudentProgress[];
}

// ================================================
// Teacher Dashboard — New/Enhanced Types
// ================================================

export interface TeacherScopedStudent {
    id: number;
    name: string;
    sectionName: string;
    gradeName: string;
    hours: number;
    quota: number;
    percentage: number;
    status: TrafficLightStatus;
}

export interface UpcomingSession {
    id: number;
    name: string;
    date: string;
    location: string;
    statusName: string;
    sectionName: string;
}

export interface EnhancedSectionProgress extends SectionProgress {
    distribution: {
        onTrack: number;
        inProgress: number;
        atRisk: number;
        zeroHours: number;
    };
    averageProgress: number;
    studentCount: number;
}

export interface EnhancedLowAttendanceStudent {
    studentId: number;
    studentName: string;
    sectionName: string;
    gradeName: string;
    sectionId: number;
    attendanceCount: number;
    totalHours: number;
}

export interface EnhancedHealthReminder {
    studentId: number;
    studentName: string;
    conditionName: string;
    severity: 'low' | 'medium' | 'high';
    lastSessionDate: string;
    daysSinceLastSession: number;
}

export interface CategoryStudent {
    studentId: number;
    studentName: string;
    sectionName: string;
    gradeName: string;
    hours: number;
    percentage: number;
}

export interface EnhancedCategoryDistribution {
    categoryName: string;
    totalHours: number;
    count: number;
    sessionCount: number;
    minRequiredHours: number | null;
    students: CategoryStudent[];
}

// ================================================
// Dashboard Data Interfaces
// ================================================

export interface AdminDashboardData {
    globalCompliance: {
        totalStudents: number;
        metQuota: number;
        onTrack: number;
        atRisk: number;
        percentage: number;
    };
    sectionRanking: SectionProgress[];
    termComparison: {
        termName: string;
        totalHours: number;
        sessionCount: number;
    }[];
    sessionStats: {
        completed: number;
        cancelled: number;
        cancellationReasons: { reason: string; count: number }[];
    };
    alerts: {
        zeroHourStudents: number;
        sessionsWithoutAttendance: number;
    };
    activityCategoryDistribution: {
        categoryName: string;
        totalHours: number;
        count: number;
    }[];
    locationDistribution: {
        locationName: string;
        totalHours: number;
        sessionCount: number;
    }[];
    teacherWorkload: {
        teacherId: number;
        teacherName: string;
        sessionCount: number;
        totalHours: number;
        averageAttendance: number;
    }[];
    yearOverYear: {
        yearName: string;
        totalHours: number;
        studentCount: number;
        averagePerStudent: number;
    }[];
    categoryDistribution: EnhancedCategoryDistribution[];
    grades: { id: number; name: string }[];
    sections: { id: number; name: string; grade_id: number }[];
    teachers: { id: number; name: string }[];
    selectedGradeId: number | null;
    selectedSectionId: number | null;
    selectedTeacherId: number | null;
}

export interface TeacherDashboardData {
    activeYear: {
        id: number;
        name: string;
        requiredHours: number;
    } | null;
    availableYears: { id: number; name: string; isActive: boolean }[];
    sections: EnhancedSectionProgress[];
    ownSessions: {
        total: number;
        completed: number;
        cancelled: number;
        totalHoursGenerated: number;
    };
    pendingAttendance: number;
    lowAttendanceStudents: EnhancedLowAttendanceStudent[];
    categoryDistribution: EnhancedCategoryDistribution[];
    sessionsPerTerm: {
        termName: string;
        count: number;
    }[];
    healthReminders: EnhancedHealthReminder[];
    // New: Student distribution
    totalStudents: number;
    distribution: {
        onTrack: number;
        inProgress: number;
        atRisk: number;
        zeroHours: number;
    };
    onTrackStudents: TeacherScopedStudent[];
    inProgressStudents: TeacherScopedStudent[];
    atRiskStudents: TeacherScopedStudent[];
    outstandingStudents: TeacherScopedStudent[];
    topStudents: TeacherScopedStudent[];
    studentsWithNoHours: TeacherScopedStudent[];
    upcomingSessions: UpcomingSession[];
    // Grade/section filters
    grades: { id: number; name: string }[];
    filterSections: { id: number; name: string; grade_id: number }[];
    selectedGradeId: number | null;
    selectedSectionId: number | null;
}

export interface StudentDashboardData {
    progress: HourAccumulation;
    breakdownByYear: {
        yearName: string;
        totalHours: number;
        quota: number;
    }[];
    breakdownByTerm: {
        termName: string;
        totalHours: number;
    }[];
    sessionHistory: {
        sessionName: string;
        date: string;
        location: string;
        hours: number;
        activities: {
            categoryName: string;
            hours: number;
            photos?: { id: number; url: string; name: string }[];
        }[];
    }[];
    closureProjection: {
        projectedDate: string | null;
        daysRemaining: number | null;
        isOnTrack: boolean;
    };
    categoryParticipation: {
        categoryName: string;
        count: number;
        totalHours: number;
    }[];
    mostRecentSession: {
        name: string;
        date: string;
        location: string;
        hours: number;
    } | null;
    sectionAverage: number;
    evidenceCount: number;
}

export interface RepresentativeStudentCard {
    id: number;
    name: string;
    cedula: string;
    email: string;
    gradeName: string;
    sectionName: string;
    hours: number;
    quota: number;
    percentage: number;
    status: TrafficLightStatus;
    nextSession: {
        name: string;
        date: string;
        location: string;
    } | null;
}

export interface RepresentativeDashboardData {
    students: RepresentativeStudentCard[];
}
