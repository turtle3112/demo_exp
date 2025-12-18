package com.vti.repository;

import com.vti.model.Organization;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface OrganizationRepository extends JpaRepository<Organization, Integer> {
    // Có thể thêm các method custom nếu cần
}