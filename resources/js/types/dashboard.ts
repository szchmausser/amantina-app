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
}

export interface TeacherDashboardData {
    sections: {
        sectionId: number;
        sectionName: string;
        gradeName: string;
        students: StudentProgress[];
    }[];
    ownSessions: {
        total: number;
        completed: number;
        cancelled: number;
        totalHoursGenerated: number;
    };
    pendingAttendance: number;
    lowAttendanceStudents: {
        studentId: number;
        studentName: string;
        sectionName: string;
        attendanceCount: number;
    }[];
    categoryDistribution: {
        categoryName: string;
        totalHours: number;
    }[];
    sessionsPerTerm: {
        termName: string;
        count: number;
    }[];
    healthReminders: {
        studentId: number;
        studentName: string;
        conditionName: string;
        lastSessionDate: string;
    }[];
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

export interface RepresentativeDashboardData {
    studentName: string;
    studentId: number;
    progress: HourAccumulation;
    last4WeeksTrend: {
        week: string;
        hours: number;
    }[];
    nextSession: {
        name: string;
        date: string;
        location: string;
    } | null;
    healthReminder: {
        hasCondition: boolean;
        conditionName: string | null;
    };
}
